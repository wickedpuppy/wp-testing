<?php

class WpTesting_Doer_TestPasser extends WpTesting_Doer_AbstractDoer
{

    /**
     * Initially we show to respondent form with test description, questions and answers
     */
    const ACTION_FILL_FORM = 'fill-form';

    /**
     * After form filled and button clicked, we show results page with scales
     */
    const ACTION_GET_RESULTS = 'get-results';

    /**
     * @var WpTesting_Model_Test
     */
    private $test = null;

    /**
     * Protection for many times calling the_content filter
     * @var string
     */
    private $filteredTestContent = null;

    public function addContentFilter()
    {
        $object        = $this->wp->getQuery()->get_queried_object();
        $isPassingPage = (is_object($object) && !empty($object->post_type) && $object->post_type == 'wpt_test');
        if (!$isPassingPage) {
            return $this;
        }
        $this->test = new WpTesting_Model_Test($object);
        $action     = $this->getTestPassingAction();
        $isDie      = (self::ACTION_FILL_FORM != $action && !$this->test->isFinal());
        if ($isDie) {
            $this->wp->dieMessage(
                __('You can not get any results from it yet.', 'wp-testing'),
                __('Test is under construction', 'wp-testing'),
                array(
                    'back_link' => true,
                    'response' => 403,
                )
            );
            return $this;
        }

        $this->wp
            ->enqueuePluginStyle('wpt_public', 'css/public.css')
            ->enqueuePluginScript('wpt_test_pass_' . $action, 'js/test-pass-' . $action . '.js', array('jquery', 'lodash'), false, true)
            ->addFilter('the_content', array($this, 'renderTestContent'))
        ;
        return $this;
    }

    public function renderTestContent($content)
    {
        // Protection for calling the_content filter not on current test content
        $isSimilar = 50 > levenshtein(
            $this->prepareToLevenshein($this->test->getContent()),
            $this->prepareToLevenshein($content)
        );
        if (!$isSimilar) {
            return $content;
        }

        // Protection for many times calling the_content filter
        if (!is_null($this->filteredTestContent)) {
            return $this->filteredTestContent;
        }
        $action   = $this->getTestPassingAction();
        $template = $this->wp->locateTemplate('entry-content-wpt-test-' . $action . '.php');
        $template = ($template) ? $template : 'Test/Passer/' . $action;

        if (self::ACTION_FILL_FORM == $action) {
            $params = array(
                'answerIdName' => fOrm::tablize('WpTesting_Model_Answer') . '::answer_id',
                'content'      => $content,
                'test'         => $this->test,
                'questions'    => $this->test->buildQuestions(),
                'isFinal'      => $this->test->isFinal(),
            );
        } elseif (self::ACTION_GET_RESULTS == $action) {
            $passing = new WpTesting_Model_Passing();
            $passing->populate($this->test);
            $params = array(
                'content'    => $content,
                'test'       => $this->test,
                'passing'    => $passing,
                'scales'     => $passing->buildScalesWithRangeOnce(),
                'results'    => $passing->buildResults(),
            );
        }

        $this->filteredTestContent = preg_replace_callback('|<form.+</form>|s', array($this, 'stripNewLines'), $this->render($template, $params));
        return $this->filteredTestContent;
    }

    private function prepareToLevenshein($input)
    {
        return substr(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($input))), 0, 255);
    }

    private function stripNewLines($matches)
    {
        return str_replace('> <', '><', preg_replace('/[\n\r\s]+/s', ' ', $matches[0]));
    }

    private function getTestPassingAction()
    {
        return $this->isPost() ? self::ACTION_GET_RESULTS : self::ACTION_FILL_FORM;
    }
}
