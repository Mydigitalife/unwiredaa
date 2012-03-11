<?php

class Widget_Html extends Unwired_Widget_Abstract
{
    public function render($content)
    {
        $data = @unserialize($content->getContent());

        if (!is_array($data)) {
            $data = array('desktop' => $content->getContent(),
                          'mobile'  => $content->getContent());
        }

        if ($this->getView()->splashPage->isMobile()) {
            return (string) $data['mobile'];
        }

        return '<h1>' . $content->getTitle() . '</h1>' . $data['desktop'];
    }

    public function renderAdmin($content, $params = array())
    {
        $this->getView()->assign($params);

        $this->getView()->content = $content;

        return $this->getView()->render('admin.phtml');
    }
}