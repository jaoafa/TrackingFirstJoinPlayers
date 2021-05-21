<?php
/**
 * Discord Embed Class
 */
class DiscordEmbed
{
    private $title; // title of embed(string)
    private $type; // type of embed(string)
    private $description; // description of embed(string)
    private $url; // url of embed(string)
    private $timestamp; // timestamp of embed content(ISO8601)
    private $color; // color code of the embed(integer)
    private $footer; // footer infomation(footer object)
    private $image; // image infomation(image object)
    private $thumbnail; // thumbnail infomation(thumbnail object)
    private $video; // video infomation(video object)
    private $provider; // provider infomation(provider object)
    private $author; // author infomation(author object)
    private $fields; // fields infomation(fields object)

    public function __construct()
    {
        // Default Setting
        $this->title = "";
        $this->type = "rich";
        $this->description = "";
        $this->url = "https://jaoafa.com/";
        $this->timestamp = date(DATE_ISO8601);
        $this->color = 65280;
        $this->footer = null;
        $this->image = null;
        $this->thumbnail = null;
        $this->video = null;
        $this->provider = null;
        $this->author = null;
        $this->fields = array();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function setTimestamp_Date($date)
    {
        if (is_int($date)) {
            // unixtime
            $date = date(DATE_ISO8601, $date);
        } else {
            // other
            $date = strtotime($date);
            $date = date(DATE_ISO8601, $date);
        }
        $this->setTimestamp($date);
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function getFooter()
    {
        return $this->footer;
    }

    public function setFooter($text, $icon_url, $proxy_icon_url)
    {
        $footer = array(
            "text" => $text,
            "icon_url" => $icon_url,
            "proxy_icon_url" => $proxy_icon_url,
        );
        $this->footer = $footer;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($url, $proxy_url, $height, $width)
    {
        $image = array(
            "url" => $url,
            "proxy_url" => $proxy_url,
            "height" => $height,
            "width" => $width,
        );
        $this->image = $image;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setThumbnail($url, $proxy_url, $height, $width)
    {
        $thumbnail = array(
            "url" => $url,
            "proxy_url" => $proxy_url,
            "height" => $height,
            "width" => $width,
        );
        $this->thumbnail = $thumbnail;
    }

    public function getVideo()
    {
        return $this->video;
    }
    
    public function setVideo($url, $height, $width)
    {
        $video = array(
            "url" => $url,
            "height" => $height,
            "width" => $width,
        );
        $this->video = $video;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setProvider($name, $url)
    {
        $provider = array(
            "name" => $name,
            "url" => $url,
        );
        $this->provider = $provider;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($name, $url, $icon_url, $proxy_icon_url)
    {
        $author = array(
            "name" => $name,
            "url" => $url,
            "icon_url" => $icon_url,
            "proxy_icon_url" => $proxy_icon_url,
        );
        $this->author = $author;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function addFields($name, $value, $inline)
    {
        $field = array(
            "name" => $name,
            "value" => $value,
            "inline" => $inline,
        );
        $this->fields[] = $field;
    }

    public function Export()
    {
        $export = array(
            "title" => $this->title,
            "type" => $this->type,
            "description" => $this->description,
            "url" => $this->url,
            "timestamp" => $this->timestamp,
            "color" => $this->color
        );
        if (!is_null($this->footer)) {
            $export["footer"] = $this->footer;
        }
        if (!is_null($this->image)) {
            $export["image"] = $this->image;
        }
        if (!is_null($this->thumbnail)) {
            $export["thumbnail"] = $this->thumbnail;
        }
        if (!is_null($this->video)) {
            $export["video"] = $this->video;
        }
        if (!is_null($this->provider)) {
            $export["provider"] = $this->provider;
        }
        if (!is_null($this->author)) {
            $export["author"] = $this->author;
        }
        if (count($this->fields) !== 0) {
            $export["fields"] = $this->fields;
        }
        return $export;
    }
}