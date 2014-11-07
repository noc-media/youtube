<?php
/**
 * User: semihs
 * Date: 24.10.14
 * Time: 13:55
 */
namespace Youtube\Downloader;

use Youtube\AbstractYoutube;
use Youtube\Downloader\Exception\GetVideoInfoException;
use Youtube\Downloader\Exception\InvalidVideoIdOrUrlException;
use Youtube\Downloader\Exception\ItagNotFoundException;
use Youtube\Downloader\Exception\ItagsIsRequiredException;
use Youtube\Downloader\Exception\LiveEventIsOverException;
use Youtube\Downloader\Exception\VideoIdOrVideoUrlRequiredException;

class Download extends AbstractYoutube
{

    protected $serviceLocator;
    protected $videoId;
    protected $videoUrl;
    protected $videoInfo;
    protected $itags;

    /**
     * @return mixed
     */
    public function getItags()
    {
        return $this->itags;
    }

    /**
     * @param mixed $itags
     */
    public function setItags($itags)
    {
        $this->itags = $itags;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * @param mixed $videoId
     */
    public function setVideoId($videoId)
    {
        $this->videoId = $videoId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVideoInfo()
    {
        if (!$this->videoInfo) {
            return $this->generateVideoInfo();
        } else {
            return $this->videoInfo;
        }
    }

    /**
     * @param mixed $videoInfo
     */
    public function setVideoInfo($videoInfo)
    {
        $this->videoInfo = $videoInfo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVideoUrl()
    {
        return $this->videoUrl;
    }

    /**
     * @param mixed $videoUrl
     */
    public function setVideoUrl($videoUrl)
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    protected function setVideoIdFromUrl($videoUrl)
    {
        $uri = \Zend\Uri\UriFactory::factory($videoUrl);
        $queryString = $uri->getQueryAsArray();
        $videoId = $queryString["v"];

        if (strlen($videoId) <= 10) {
            throw new InvalidVideoIdOrUrlException("Invalid Video Id or Video Url.");
        }

        $this->setVideoId($videoId);

        return $this;
    }

    protected function pathSafeFilename($string)
    {
        $regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\-]#', '#^\.#');
        return preg_replace($regex, '_', $string);
    }

    protected function getExtension($mimetype)
    {
        $mime = new \Dflydev\ApacheMimeTypes\FlatRepository;
        $extension = 'mp4';
        $extensions = $mime->findExtensions($mimetype);
        if (count($extensions)) {
            $extension = $extensions[0];
        }

        return $extension;
    }

    protected function generateVideoInfo()
    {
        if (!$this->getVideoId() && !$this->getVideoUrl()) {
            throw new VideoIdOrVideoUrlRequiredException("Video Id or Video url required.");
        }
        if (empty($this->getVideoId())) {
            $this->setVideoIdFromUrl($this->getVideoUrl());
        }

        $client = new \Zend\Http\Client($this->getVideoInfoUrl() . $this->getVideoId(), array());
        parse_str($client->send(), $response);

        if (isset($response['status']) && $response['status'] == 'fail') {
            throw new GetVideoInfoException($response['reason'], $response['errorcode']);
        }

        $result = array();
        $result['title'] = $response['title'];
        $result['image'] = array(
            'max_resolution' => $this->getThumbnailUrl() . $this->getVideoId() . '/maxresdefault.jpg',
            'high_quality' => $this->getThumbnailUrl() . $this->getVideoId() . '/hqdefault.jpg',
            'standard' => $this->getThumbnailUrl() . $this->getVideoId() . '/sddefault.jpg',
            'thumbnail' => $this->getThumbnailUrl() . $this->getVideoId() . '/default.jpg'
        );
        if (!empty($response['length_seconds'])) {
            $result['length_seconds'] = $response['length_seconds'];
        }

        $filename = $this->pathSafeFilename($result['title']);

        if (isset($response['ps']) && $response['ps'] = 'live') {
            if (!isset($response['hlsvp'])) {
                throw new LiveEventIsOverException('This live event is over.', 2);
            }

            $result['stream_url'] = $response['hlsvp'];
        } else {
            if (!empty($response['url_encoded_fmt_stream_map'])) {
                $streamMaps = explode(',', $response['url_encoded_fmt_stream_map']);
                foreach ($streamMaps as $key => $value) {
                    parse_str($value, $streamMaps[$key]);
                    if (!empty($streamMaps[$key]['sig'])) {
                        $streamMaps[$key]['url'] .= '&signature=' . $streamMaps[$key]['sig'];
                        unset($streamMaps[$key]['sig']);
                    }

                    $typeParts = explode(';', $streamMaps[$key]['type']);
                    $streamMaps[$key]['filename'] = $filename . '.' . $this->getExtension(trim($typeParts[0]));

                    $streamMaps[$key] = (object)$streamMaps[$key];
                }
                $result['full_formats'] = $streamMaps;
            }

            $adaptiveFmts = explode(',', $response['adaptive_fmts']);
            foreach ($adaptiveFmts as $key => $value) {
                parse_str($value, $adaptiveFmts[$key]);

                $typeParts = explode(';', $adaptiveFmts[$key]['type']);
                $adaptiveFmts[$key]['filename'] = $filename . '.' . $this->getExtension(trim($typeParts[0]));

                $adaptiveFmts[$key] = (object)$adaptiveFmts[$key];
            }
            $result['adaptive_formats'] = $adaptiveFmts;
        }

        $result['video_url'] = $this->getWatchUrl() . $this->getVideoId();

        $this->setVideoInfo((object)$result);

        return $result;
    }

    public function download($location, $chunkSize = 0)
    {
        if (empty($this->getItags())) {
            throw new ItagsIsRequiredException("Minimum 1 itag required.");
        }

        $videoInfo = $this->getVideoInfo();
        $response = array();
        foreach ($this->getItags() as $itag) {
            $formatItag = null;
            foreach ($videoInfo['full_formats'] as $format) {
                if ($itag == $format->itag) {
                    $formatItag = $format;
                }
            }
            foreach ($videoInfo['adaptive_formats'] as $format) {
                if ($itag == $format->itag) {
                    $formatItag = $format;
                }
            }
            if (empty($formatItag)) {
                throw new ItagNotFoundException("Itag Not Found ({$itag})");
            }

            $pathInfo = pathinfo($location);
            mkdir($pathInfo['dirname'], 777, true);

            //$location = /var/www/uploads/$fileName%s.mp4
            $locationFormatted = sprintf($location, $itag);
            $target = fopen($locationFormatted, "a");

            $ch = curl_init($formatItag->url);
            curl_setopt($ch, CURLOPT_REFERER, $formatItag->url);
            curl_setopt($ch, CURLOPT_HEADER, array(
                "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36",
                "Connection: keep-alive",
                "Keep-Alive: 115",
                "Accept-Encoding: gzip,deflate",
                "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.10) Gecko/20100914 Firefox/3.6.10",
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
            ));
            if (!empty($chunkSize)) {
                $start = filesize($locationFormatted);
                $end = $start + $chunkSize;
                curl_setopt($ch, CURLOPT_RANGE, "{$start}-{$end}");

                $response[$itag]['progress'] = ($end / $formatItag->clen) * 100;
            } else {
                $response[$itag]['progress'] = 100;
            }
            if ($response[$itag]['progress'] <= 100) {
                curl_setopt($ch, CURLOPT_CAPATH, "/etc/ssl/certs/");
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                curl_setopt($ch, CURLOPT_FILE, $target);
                curl_exec($ch);
                curl_close($ch);
                fclose( $target );
            } else {
                $response[$itag]['progress'] = 100;
            }
        }
        return $response;
    }
}
