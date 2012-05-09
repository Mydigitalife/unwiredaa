<?php

class Captive_Service_SplashPage
{
    /**
     * Find the active splash page for a device group
     *
     * @param Captive_Model_Group $group
     * @return Captive_Model_SplashPage
     */
    public function findActiveSplashPage(Captive_Model_Group $group)
    {
        $mapperSplash = new Captive_Model_Mapper_SplashPage();

        $splashPage = null;

        $parent = $group;
        /**
         * Walk the tree up to the root node.
         * It is assumed that at least the root node will have active splash page set.
         */
        do {
            $splashPage = $mapperSplash->findOneBy(array('active' => 1,
                                                         'selected' => 1,
                                                         'group_id' => $group->getGroupId()));

            if ($splashPage) {
                return $splashPage;
            }

            $parent = $parent->getParent();
        } while (null !== $parent);

        return null;
    }

    public function getSplashPageContents(Captive_Model_SplashPage $splashPage/*,
                                          Captive_Model_Language $language*/)
    {
        $mapperContent = new Captive_Model_Mapper_Content();

        $criteria = array('splash_id'   => $splashPage->getSplashId(),
                          /*'language_id' => $language->getLanguageId(),
                          'mobile'      => 0,*/
                          'type'        => 'content');

      /*  if ($splashPage->isMobile()) {
            $criteria['mobile'] = 1;
        }*/

        $contents = $mapperContent->findBy($criteria/*,
                                           null,
                                           '`order` ASC'*/);

        $templateOverrideIds = array();

        foreach ($contents as $content) {
            if ($content->getTemplateContent()) {
                $templateOverrideIds[] = $content->getTemplateContent();
            }
        }

        unset($criteria['splash_id']);
        $criteria['template_id'] = $splashPage->getTemplateId();

        $templateContents = $mapperContent->findBy($criteria/*,
                                                   null,
                                                   '`order` ASC'*/);

        foreach ($templateContents as $index => $content) {
            if (in_array($content->getContentId(), $templateOverrideIds)) {
                unset($templateContents[$index]);
            }
        }

        $contents = array_merge($contents, $templateContents);

        return $contents;

    }

    public function getTemplateContent(Captive_Model_Template $template)
    {
        $mapperContent = new Captive_Model_Mapper_Content();

        $contents = $mapperContent->findBy(array('template_id' => $template->getTemplateId()));

        return $contents;
    }

    public function updateOrder($desktop, $mobile, $template = false, $splashId = 0, $templateId = 0)
    {
        $mapperContent = new Captive_Model_Mapper_Content();

        $contents = array('desktop' => $desktop, 'mobile' => $mobile);

        $forUpdate = array();

        foreach ($contents as $layoutName => $layout) {
            foreach ($layout as $column => $order) {
                $column = (int) $column;

                $position = 0;

                foreach ($order as $contentId) {
                    $position++;
                    $contentId = (int) $contentId;

                    if (!isset($forUpdate[$contentId])) {
                        $content = $mapperContent->find($contentId);
                    } else {
                        $content = $forUpdate[$contentId];
                    }

                    if (!$content) {
                        continue;
                    }

                    if ($template && $content->getSplashId()) {
                        continue;
                    }

                    $pageContent = null;
                    if (!$template && $content->getTemplateId()) {
                        /**
                         * Content is not editable
                         */
                        if (!$content->isEditable()) {
                            continue;
                        }

                        $pageContent = $mapperContent->findOneBy(array('template_content' => $content->getContentId()));

                        if (!$pageContent) {
                            /**
                             * Clone the content so we can move it
                             */
                            $pageContent = clone $content;
                            $pageContent->setContentId(null)
                                        ->setTemplateId(null)
                                        ->setTemplateContent($content->getContentId())
                                        ->setSplashId($splashId);

                            try {
                                $mapperContent->save($pageContent);
                            } catch (Exception $e) {
                                continue;
                            }
                        }
                    }

                    /**
                     * Check and swap template widget with splashpage widget
                     */
                    if ($pageContent) {
                        $forUpdate[$content->getContentId()] = $pageContent;
                        $content = $pageContent;
                    } else {
                        $forUpdate[$content->getContentId()] = $content;
                    }

                    if ($layoutName == 'desktop') {
                        $content->setColumn($column);
                    }

                    $data = $content->getData();

                    foreach($data as $contentData) {
                        if ($layoutName == 'desktop') {
                            if($contentData->isMobile()) {
                                continue;
                            }
                        } else {
                            if(!$contentData->isMobile()) {
                                continue;
                            }
                        }

                        $contentData->setOrder($position);
                    }
                }
            }
        }

        foreach ($forUpdate as $content) {
            $mapperContent->save($content);
        }

        return true;
    }

    public function saveTemplateContents(Captive_Model_Template $template, array $contents)
    {
        /**
         * @todo this is quick and dirty workaround for 22nd Nov
         */

        $mapperContent = new Captive_Model_Mapper_Content();

        $success = 0;

        foreach ($contents as $type => $languageData) {
            if ($type != 'terms' && $type != 'imprint') {
                continue;
            }

            foreach ($languageData as $languageId => $content) {
                try {
                    $model = $mapperContent->getEmptyModel();
                    $model->fromArray($content);
                    $model->setLanguageId($languageId)
                          ->setType($type)
                          ->setTemplateId($template->getTemplateId());

                    $mapperContent->save($model);
                    $success++;
                } catch (Exception $e) {
                    throw $e;
                }
            }
        }

        /**
         * @todo add html content blocks
         */
        return $success;
    }

    public function getGroupTemplates($group)
    {
        $groupId = $group;
        if ($groupId instanceof  Groups_Model_Group) {
            $groupId = $group->getGroupId();
        }

        $serviceGroups = new Groups_Service_Group();

        $group = $serviceGroups->findGroup($groupId, true, false);

        if (!$group) {
            return array();
        }

        $ids = array($group->getGroupId());

        $parent = $group;
        while ($parent = $parent->getParent()) {
            $ids[] = $parent->getGroupId();
        }

        $mapperTemplate = new Captive_Model_Mapper_Template();

        $templates = $mapperTemplate->findBy(array('group_id' => $ids));

        if (!$templates) {
            $templates = array();
        }

        return $templates;
    }

    public function saveSplashPageContents(Captive_Model_SplashPage $splashPage, array $contents)
    {
        /**
         * @todo this is quick and dirty workaround for 22nd Nov
         */

        $mapperContent = new Captive_Model_Mapper_Content();

        $success = 0;

        foreach ($contents as $content) {
            try {
                $model = null;
                if (isset($content['content_id'])) {
                    $model = $mapperContent->find($content['content_id']);

                }

                if (!$model) {
                    $model = $mapperContent->getEmptyModel();
                }

                $model->fromArray($content);

                if ($model->getTemplateId()) {
                    $model->setTemplateContent($model->getContentId());
                    $model->setTemplateId(null);
                    $model->setContentId(null);

                }


                $model->setType('content')
                      ->setSplashId($splashPage->getSplashId());

                $mapperContent->save($model);
                $success++;
            } catch (Exception $e) {
                throw $e;
            }
        }

        /**
         * @todo add html content blocks
         */
        return $success;
    }
}