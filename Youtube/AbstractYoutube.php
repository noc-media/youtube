<?php
/**
 * Created by PhpStorm.
 * User: semihs
 * Date: 24.10.14
 * Time: 14:26
 */

namespace Youtube;

abstract class AbstractYoutube {

    protected $videoInfoUrl;
    protected $thumbnailUrl;
    protected $watchUrl;

    /**
     * @return mixed
     */
    public function getThumbnailUrl()
    {
        if (!$this->thumbnailUrl) {
            $this->thumbnailUrl = "http://i1.ytimg.com/vi/";
        }
        return $this->thumbnailUrl;
    }

    /**
     * @param mixed $thumbnailUrl
     */
    public function setThumbnailUrl($thumbnailUrl)
    {
        $this->thumbnailUrl = $thumbnailUrl;
    }

    /**
     * @return mixed
     */
    public function getVideoInfoUrl()
    {
        if (!$this->videoInfoUrl) {
            $this->videoInfoUrl = "http://www.youtube.com/get_video_info?el=detailpage&ps=default&eurl=&gl=US&hl=en&sts=15888&video_id=";
        }
        return $this->videoInfoUrl;
    }

    /**
     * @param mixed $videoInfoUrl
     */
    public function setVideoInfoUrl($videoInfoUrl)
    {
        $this->videoInfoUrl = $videoInfoUrl;
    }

    /**
     * @return mixed
     */
    public function getWatchUrl()
    {
        if (!$this->watchUrl) {
            $this->watchUrl = "http://www.youtube.com/watch?v=";
        }
        return $this->watchUrl;
    }

    /**
     * @param mixed $watchUrl
     */
    public function setWatchUrl($watchUrl)
    {
        $this->watchUrl = $watchUrl;
    }
}