<?php

/**
 * baseTotalReport.php
 * modified by: ariane adajar<arianeadajar@gmail.com>
 * modified date: 2/20/2017
 */
/**
 * Description of baseSchedulingReport
 */

abstract class baseSchedulingReport extends sfTCPDF
{

    /** @var sfParameterHolder */
    private $parameterHolder = null;

    /**
     * @inheritdoc
     */
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = "UTF-8", $diskCache=false, $pdfa=false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskCache, $pdfa);
        $this->initialize();
    }

    /**
     * The bulk of the report's operations should be placed in this method
     *
     * @return void
     */
    abstract protected function execute();

    /**
     *
     */
    protected function initialize()
    {
    }

    /**
     * Controllers should call this method once the report is ready for output
     *
     * @param string $name
     * @param string $dest
     */
    public function render($name = 'doc.pdf', $dest = 'I')
    {
        $this->execute();
        $this->Output($name, $dest);
    }

    /**
     * @return sfParameterHolder
     */
    public function getParameterHolder()
    {
        if (empty($this->parameterHolder)) {
            $this->parameterHolder = new sfParameterHolder();
        }
        return $this->parameterHolder;
    }

    /**
     * @param sfParameterHolder $parameterHolder
     */
    public function setParameterHolder($parameterHolder)
    {
        $this->parameterHolder = $parameterHolder;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute($name, $value)
    {
        $this->getParameterHolder()->set($name, $value);
    }

    /**
     * @param $name
     * @param mixed|null $default
     */
    public function getAttribute($name, $default = null)
    {
        return $this->getParameterHolder()->get($name, $default);
    }
}
