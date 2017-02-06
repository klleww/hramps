
<?php

/**
 * added by: ariane
 * added on: 02/03/2017
 */
class ohrmReportWidgetClassification extends sfWidgetForm implements ohrmEnhancedEmbeddableWidget {

    private $whereClauseCondition;

    public function configure($options = array(), $attributes = array()) {

        $classificationTypes = $this->_getClassificationTypes();

        $this->addOption('choices', $classificationTypes);
    }

    public function render($name, $value = null, $attributes = array(), $errors = array()) {
        $value = $value === null ? 'null' : $value;

        $options = array();

        foreach ($this->getOption('choices') as $key => $option) {
            $attributes = array('value' => self::escapeOnce($key));

            if ($key == $value) {
                $attributes['selected'] = 'selected';
            }

            $options[] = $this->renderContentTag(
                            'option',
                            self::escapeOnce($option),
                            $attributes
            );
        }

        $html = $this->renderContentTag(
                        'select',
                        "\n" . implode("\n", $options) . "\n",
                        array_merge(array('name' => $name), $attributes
                ));

        return $html;
    }

    /**
     * Gets all the classification types
     * @return string[] $classificationTypes
     */
    private function _getClassificationTypes() {

        $classificationType = array('-1' => __('All'), 'Faculty' => __('Faculty'), 'Faculty w/ Admin Function' => __('Faculty w/ Admin Function'),'Staff' => __('Staff'));

        return $classificationType;
    }

    /**
     * Embeds this widget into the form. Sets label and validator for this widget.
     * @param sfForm $form
     */
    public function embedWidgetIntoForm(sfForm &$form) {

        $requiredMess = 'Select a classification type';

        $widgetSchema = $form->getWidgetSchema();
        $widgetSchema[$this->attributes['id']] = $this;
        $label = ucwords(str_replace("_", " ", $this->attributes['id']));
        $validator = new sfValidatorString();
        if (isset($this->attributes['required']) && ($this->attributes['required'] == "true")) {
            $label .= "<span class='required'> * </span>";
            $validator = new sfValidatorString(array('required' => true), array('required' => $requiredMess));
        }
        $widgetSchema[$this->attributes['id']]->setLabel($label);
        $form->setValidator($this->attributes['id'], $validator);
    }

    /**
     * Sets whereClauseCondition.
     * @param string $condition
     */
    public function setWhereClauseCondition($condition) {

        $this->whereClauseCondition = $condition;
    }

    /**
     * Gets whereClauseCondition. ( if whereClauseCondition is set returns that, else returns default condition )
     * @return string ( a condition )
     */
    public function getWhereClauseCondition() {

        if (isset($this->whereClauseCondition)) {
            $setCondition = $this->whereClauseCondition;
            return $setCondition;
        } else {
            $defaultCondition = "=";
            return $defaultCondition;
        }
    }

    /**
     * This method generates the where clause part.
     * @param string $fieldName
     * @param string $value
     * @return string
     */
    public function generateWhereClausePart($fieldName, $value) {

        if ($value == '-1') {
            $whereClausePart = null;
        } else if ($value == 'Faculty') {
            $whereClausePart = $fieldName . " " . $this->getWhereClauseCondition() . " " . "Faculty";
        } else if ($value == 'Faculty w/ Admin Function') {
            $whereClausePart = $fieldName . " " . $this->getWhereClauseCondition() . " " . "Faculty w/ Admin Function";
        } else {
            $whereClausePart = $fieldName . " " . $this->getWhereClauseCondition() . " " . "Staff";
        }

        return $whereClausePart;
    }

    public function getDefaultValue(SelectedFilterField $selectedFilterField) {
        return $selectedFilterField->value1;
    }
}

