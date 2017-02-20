<?php

/**
 * TotalPrintout.php
 * modified by: ariane adajar<arianeadajar@gmail.com>
 * modified date: 2/20/2017
 */
 
/**
 * Description of SchedulePrintout
 */
 
class SchedulePrintout extends baseSchedulingReport
{

    const BORDER_NONE = 0;

    const LABEL_COL_WIDTH = 35;

    /** @var int */
    private $dataColumns = 7;
    /** @var bool */
    private $breakBetweenDataSets = false;
    /** @var array  */
    private $notes = array();
    /** @var SchedulingEmployee[] */
    private $employeeData = array();
    /** @var array */
    private $workShiftData = array();
    /** @var DateTime Used to keep track of the currently rendered dates  */
    private $startDate = null;
    /** @var DateTime Used to keep track of the currently rendered dates  */
    private $endDate = null;
    /** @var DateTime Used to keep track of the currently rendered dates  */
    private $currentDate = null;

    /**
     * @inheritdoc
     */
    protected function initData()
    {
        /** @var DateTime $startDate */
        $this->startDate = $this->getAttribute('startDate');
        /** @var DateTime $endDate */
        $this->endDate = $this->getAttribute('endDate');
        $this->startDate->setTime(0,0,0);
        $this->endDate->setTime(0,0,0);

        $this->currentDate = clone $this->startDate;
        $this->employeeData = $this->getAttribute('employeeData');
        $this->workShiftData = $this->getAttribute('workShiftData');

        $this->dataColumns = (int) $this->getAttribute('columns', 7);
        $this->breakBetweenDataSets = $this->getAttribute('breakBetweenDataSets', false);

    }

    /**
     * Executes the action
     *
     */
    protected function execute()
    {
        $this->initData();

        // pdf object
//        $this->SetLineWidth(0.05);
        $this->SetLineStyle(array(
            'width' => 0.1,
            'cap' => 'square',
        ));

        // set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor(PDF_AUTHOR);
        $this->SetTitle('');
        $this->SetSubject('');
        $this->SetKeywords('');

        // set default header data
        //$this->setHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING);

        // set header and footer fonts
        $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set margins
        $topOffset = 0;
        $topMargin = PDF_MARGIN_TOP + $topOffset;

        $this->SetMargins(PDF_MARGIN_LEFT, $topMargin, PDF_MARGIN_RIGHT);
        $this->setHeaderMargin(PDF_MARGIN_HEADER);
        $this->setFooterMargin(PDF_MARGIN_FOOTER);

        //set auto page breaks
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------

        // set default font subsetting mode
        $this->setFontSubsetting(true);

        // Add a page
        // This method has several options, check the source code documentation for more information.
        $this->AddPage();

        $this->SetFont('helvetica', 'B', 14, '', true);
        $this->Cell(0, 6, 'Work Schedule', self::BORDER_NONE, 1, 'C');
        $this->Ln(3);


        $labelW = 22;
        $dataW = 36;
        $separatorW = $this->w - $this->lMargin - $this->rMargin - $labelW * 2 - $dataW * 2;


        $this->SetFont('', '', 9);
        $this->Cell($labelW, 4, 'Period:', self::BORDER_NONE, 0, 'L');
        $this->SetFont('', 'B');

        $this->Cell($dataW, 4, $this->renderDateRange($this->startDate, $this->endDate), self::BORDER_NONE, 0);

        $this->Cell($separatorW,4);
        $this->SetFont('', '');
        $this->Cell($labelW, 4, 'Date/time:', self::BORDER_NONE, 0, 'L');
        $this->SetFont('', 'B');
        $this->Cell($dataW, 4, date('M d, Y h:ia'), self::BORDER_NONE, 1);

        $this->SetFont('', '');
        $this->Cell($labelW, 4, 'Prepared by:', self::BORDER_NONE, 0, 'L');
        $this->SetFont('', 'B');

        $this->Cell($dataW, 4, $this->getAttribute('preparedBy'), self::BORDER_NONE, 0);

        $this->Cell($separatorW,4);
        $this->SetFont('', '');
        $this->Cell($labelW, 4, 'Status:', self::BORDER_NONE, 0, 'L');
        $this->SetFont('', 'B');
        $this->Cell($dataW, 4, $this->getAttribute('isFinalized') ? 'Finalized' : 'Not Yet Finalized', self::BORDER_NONE, 1);

        if ($this->getAttribute('departmentName')) {
            $this->SetFont('', '');
            $this->Cell($labelW, 4, 'Department:', self::BORDER_NONE, 0, 'L');
            $this->SetFont('', 'B');
            $this->Cell($dataW, 4, $this->getAttribute('departmentName'), self::BORDER_NONE, 1);
        } else {
            $this->SetFont('', '');
            $this->Cell($labelW, 4, 'Department:', self::BORDER_NONE, 0, 'L');
            $this->SetFont('', 'B');
            $this->Cell($dataW, 4, $this->getDepartmentsSummary(), self::BORDER_NONE, 1);
        }

        $this->Ln(4);

        $this->renderHeader();
        $this->renderData();

        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        $this->Output('schedule.pdf', 'I');

    }

    /**
     *
     */
    public function Header()
    {

        if ($this->header_xobjid === false) {

            // start a new XObject Template
            $this->header_xobjid = $this->startTemplate(0, $this->tMargin);

            $logoPath = sfConfig::get('sf_root_dir') . '/web/themes/default/images/logo.png';
            $this->Image($logoPath, $this->lMargin, 11, 36);

            $x = $this->getImageRBX() + 2;
            $y = 10;
            $this->SetXY($x, $y);
            $this->SetFont('', 'B', 9);
            $this->Cell(0, 4, sfConfig::get('app_hospital_name'), 0, 1);

            $this->SetFont('', '', 9);

            $this->SetX($x);
            $this->Cell(0, 3.5, sfConfig::get('app_address1'), 0, 1);

            $this->SetX($x);
            $this->Cell(0, 3.5, sfConfig::get('app_address2'), 0, 0);

            $this->Ln(2.5);
            $this->Cell(0, 1, '', 'B', 0, 1);

            $this->endTemplate();
        }

        $x = $this->lMargin;
        $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);

        if ($this->header_xobj_autoreset) {
            // reset header xobject template at each page
            $this->header_xobjid = false;
        }

    }

    /**
     *
     */
    public function Footer()
    {
        $x = -28;
        $y = -20;
        $this->SetY($y);
        $this->Cell(0, 3, '', 'T', 0);
        $this->Ln(2);

        $this->SetX($x);
        $this->SetFont('', '', 9);
        $this->Cell(0, 3.5, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, 1, 'L');
        $this->Cell(0, 3.5, 'Segworks Human Resource Information System', 0, 0, 'R');
    }

    /**
     *
     */
    private function renderHeader()
    {
        $current = clone $this->currentDate;
        $end = clone $current;
        $end->add(new DateInterval('P'.($this->dataColumns-1).'D'));

        if ($end > $this->endDate) {
            $end = clone $this->endDate;
        }


        $this->SetFont('', 'B', 11);
        $this->Cell(0, 8, 'Schedule for ' . $this->renderDateRange($current, $end), self::BORDER_NONE, 1, 'L');
        $this->Ln(1);

        $labelW = self::LABEL_COL_WIDTH;
        $dataW= ($this->w - $this->lMargin - $this->rMargin - $labelW) / $this->dataColumns;

        $this->SetFont('', 'B', 9);

        $thRowH = 7;
        $this->Cell($labelW, $thRowH, 'Employee', 'TLB', 0, 'C');

        while ($current <= $end) {
            $this->Cell($dataW, $thRowH, strtoupper($current->format('D j')), 'TLB', 0, 'C');
            $current->add(new DateInterval('P1D'));
        }

        $this->Cell(0, $thRowH, '', 'L', 1);

    }

    /**
     * @param $startDate
     * @param $endDate
     */
    private function renderData()
    {
        $leftRightPadding = 2;
        $this->setCellPaddings($leftRightPadding, 0, $leftRightPadding, 0);

        $labelW = self::LABEL_COL_WIDTH;
        $dataW= ($this->w - $this->lMargin - $this->rMargin - $labelW) / $this->dataColumns;

        $entryH = 5;
        $topBottomPadding = 1;

        foreach ($this->employeeData as $empId => $employee) {

            $canRead = $employee->getScheduleReadPermission();

            $current = clone $this->currentDate;
            $end = clone $current;
            $end->add(new DateInterval('P' . ($this->dataColumns - 1) . 'D'));
            if ($end > $this->endDate) {
                $end = clone $this->endDate;
            }

            $rowEntries = array();
            $maxEntries = 1;

            while ($canRead && $current <= $end) {

                /** @var WorkShiftEntry[] $currentEntries */
                $currentEntries = @$this->workShiftData[$current->format('Y-m-d')];
                if (!empty($currentEntries)) {

                    /** @var WorkShiftEntry[] $entries */
                    $entries = array();
                    foreach ($currentEntries as $entry) {

                        if ($entry->getEmployeeId() == $empId) {
                            $entries[] = $entry;
                        }
                    }

                    if ($maxEntries < sizeof($entries)) {
                        $maxEntries = sizeof($entries);
                    }

                    $rowEntries[] = $entries;

                } else {
                    $rowEntries[] = array();
                }

                $current->add(new DateInterval('P1D'));
            } # while ($current <= $end) ...

            $maxH = $maxEntries * $entryH + $topBottomPadding * 2;

            $x = $this->GetX();

            // Render empty cell first to trigger Page break
            $y = $this->GetY();
            $this->Cell(0, $maxH, '', 0, 0);

            if ($this->GetY() < $y) {
                // We have a page break
                $this->SetX($x);
                $this->renderHeader();

            }

            $this->SetX($x);

            $this->SetFont('', '', 9);
            $this->MultiCell($labelW, $maxH, $employee->getLastName() . ', ' . $employee->getFirstName(), 'BL', 'L', false, 0, '', '', true, 0, false, true, $maxH, 'M', true);

            if ($canRead) {

                foreach ($rowEntries as $entries) {

                    if ($entries) {

                        $x = $this->GetX();
                        $y = $this->GetY();

                        $this->SetY($this->GetY() + $topBottomPadding, false);
                        foreach ($entries as $entry) {

                            $this->SetFont('', '', 9);
                            $shift = trim($entry->getShift()->getName());

                            $noteX = $this->GetX();
                            $noteY = $this->GetY();

                            $this->MultiCell(
                                $dataW,
                                $entryH,
                                $shift,
                                $border = 0,
                                $align = 'C',
                                $fill = false,
                                $ln = 0,
                                $xx = '', $yy = '',
                                $resetTh = true,
                                $stretch = 0,
                                $isHtml = false,
                                $autoPadding = true,
                                $maxHeight = $entryH,
                                $vAlign = 'M',
                                $fitCell = true
                            );

                            if ($this->getAttribute('showNotes') && $entry->getRemarks()) {
                                $this->notes[] = $entry->getRemarks();

                                $fontSize = $this->autofitFontSize;
                                $this->SetFontSize($fontSize);
                                $this->SetXY($noteX + $dataW / 2.0 + $this->GetStringWidth($shift) / 2.0 + 0.25, $noteY - 0.5);
                                $this->SetFontSize($fontSize * 0.75);
                                $pad = $this->getCellPaddings();
                                $this->setCellPaddings(0, 0, 0, 0);

                                $this->Write(4, sizeof($this->notes), 1, 0, 'L');
                                $this->setCellPaddings($pad['L'], $pad['T'], $pad['R'], $pad['B']);

                            }

                            $this->SetXY($noteX, $noteY + $entryH);
                        }

                        $this->SetXY($x, $y);
                        $this->Cell($dataW, $maxH, '', 'BL', 0);
                        // Render border here
                    } else {

                        $this->Cell($dataW, $maxH, '', 'BL', 0);

                    }

                } #foreach ($rowEntries as $entries) ...

                $this->Cell(0, $maxH, '', 'R', 1);

            } else {

                $this->SetFont('', 'I', 9);
                $this->Cell(0, $maxH, 'You are not allowed to view this employee\'s schedule ...', 'BLR', 1);

            }

        }


        $this->Ln(5);

        $this->currentDate->add(new DateInterval('P' . $this->dataColumns . 'D'));
        if ($this->currentDate <= $this->endDate) {

            if ($this->breakBetweenDataSets) {
                $this->AddPage();
                $this->renderHeader();
            } else {
                $this->renderHeader();
            }

            $this->renderData();
        } else {

            if ($this->getAttribute('showNotes')) {
                $this->renderNotes();
            }
        }
    }

    /**
     *
     */
    private function renderNotes()
    {
        $this->SetFont('', 'B', 11);
        $this->Cell(0, 8, 'Notes', 'B', 1, 'L');
        $this->Ln(2);

        $this->SetFont('', '', 8);
        foreach ($this->notes as $i => $note) {
            $this->MultiCell(0, 4, '<sup>' . ($i+1) .'</sup> ' . $note, 0, 'L', 0, 1, '', '', true, 0, true);
        }
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return string
     */
    private function renderDateRange(DateTime $start, DateTime $end)
    {
        if ($start == $end) {
            return $start->format('M j, Y');
        } else {
            return $start->format('M j') . ' - ' . $end->format('M j, Y');
        }
    }

    /**
     * @return string
     */
    private function getDepartmentsSummary()
    {
        $departments = array();
        foreach ($this->employeeData as $employee) {
            $departments[] = $employee->getSubDivision()->getName();
            $departments = array_unique($departments);
        }

        if (sizeof($departments) >= 4) {
            $size = sizeof($departments);
            return strtr('{departments} and {count} more', array(
                '{departments}' => implode(', ', array_slice($departments, 0, 2)),
                '{count}' => $size-2,
            ));
        } else {
            return implode(', ', $departments);
        }
    }

}
