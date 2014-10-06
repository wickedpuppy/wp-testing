<?php

class WpTesting_Model_Passing extends WpTesting_Model_AbstractModel
{

    /**
     * @param WpTesting_Model_Test $test
     * @return WpTesting_Model_Passing
     */
    public function populate(WpTesting_Model_Test $test)
    {
        $this->setTestId($test->getId());
        parent::populate(true);

        $answers = $this->buildAnswers();
        foreach ($test->buildQuestions() as $i => $question) {
            $answers[$i]->setQuestionId($question->getId());
        }

        return $this;
    }

    /**
     * @return WpTesting_Model_PassingAnswer[]
     */
    public function buildAnswers()
    {
        return $this->buildWpTesting_Model_PassingAnswers();
    }

    /**
     * Build scales and setup their ranges from test's questions
     *
     * @return WpTesting_Model_Scale[]
     */
    public function buildScalesWithRange()
    {
        $result = array();
        foreach ($this->createTest()->buildScalesWithRange() as $testScale) {
            $scale = clone $testScale;
            $result[$testScale->getId()] = $testScale;
        }

        $scoresByScales = array_fill_keys(array_keys($result), 0);
        foreach ($this->buildAnswers() as $passingAnswer) {
            $scores = $passingAnswer->createQuestion()->getScoresByAnswer($passingAnswer->createAnswer());
            foreach ($scores as $score) { /* @var $score WpTesting_Model_Score */
                $scoresByScales[$score->getScaleId()] += $score->getValue();
            }
        }

        foreach ($result as $id => $scale) {
            $scale->setValue($scoresByScales[$id]);
        }

        return fRecordSet::buildFromArray('WpTesting_Model_Scale', $result);
    }

    /**
     * @return WpTesting_Model_Test
     */
    protected function createTest()
    {
        return $this->createWpTesting_Model_Test();
    }
}
