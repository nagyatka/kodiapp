<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 18:47
 */

namespace KodiApp\Response;


class FileResponse
{
    private $filename;
    private $contenttype;
    private $send_name;

    /**
     * FileResponse constructor.
     * @param $filename
     * @param $contenttype
     * @param $send_name
     */
    public function __construct($filename, $contenttype, $send_name)
    {
        $this->filename = $filename;
        $this->contenttype = $contenttype;
        $this->send_name = $send_name;
    }


    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    function __toString()
    {
        if (file_exists($this->filename)) {
            header('Content-Description: File Transfer');
            header('Content-Type: '.$this->contenttype);
            header('Content-Disposition: inline; filename="'.$this->send_name.'"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($this->filename));
            ob_clean();
            flush();
            readfile($this->filename);
            return "";
        } else {
            return new ErrorResponse(404);
        }
    }


}