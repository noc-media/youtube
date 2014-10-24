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
            $configs = $this->getServiceLocator()->get('Config');
            $this->thumbnailUrl = $configs['thumbnailUrl'];
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
            $configs = $this->getServiceLocator()->get('Config');
            $this->videoInfoUrl = $configs['videoInfoUrl'];
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
            $configs = $this->getServiceLocator()->get('Config');
            $this->watchUrl = $configs['watchUrl'];
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