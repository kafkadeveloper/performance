<?php namespace Performance\Lib\Presenters;

use Performance\Lib\Handlers\PerformanceHandler;
use Performance\Lib\Point;

class ConsolePresenter extends Presenter
{
    private $commandLineWidth;
    private $commandLineHeight;
    private $cellWightResult;
    private $cellWightLabel;

    public function bootstrap()
    {
        $this->printStartUp();
    }

    public function finishPointTrigger(Point $point)
    {
        // Preload and calculate
        if($point->getLabel() === Point::POINT_PRELOAD)
            return;

        $this->liveOrStack(
            str_pad(mb_strimwidth( " > " . $point->getLabel(), 0, $this->cellWightLabel, '..'), $this->cellWightLabel)
            . ' ' . $this->formatter->stringPad( $this->formatter->timeToHuman( $point->getDifferenceTime() ). ' ', $this->cellWightResult, " ")
            . '|' . str_pad( $this->formatter->memoryToHuman( $point->getDifferenceMemory() ) . ' ', $this->cellWightResult, " ", STR_PAD_LEFT)
            . '|' . str_pad( $this->formatter->memoryToHuman( $point->getMemoryPeak() ) . ' ', $this->cellWightResult, " ", STR_PAD_LEFT) . PHP_EOL);

        // Print query log resume
        $this->printPointQueryLogAsNewLineMessage($point);

        // Print point new line message
        $this->printPointNewLineMessage($point);
    }

    public function displayResultsTrigger($pointStack)
    {
        $this->pointStack = $pointStack;
        $this->printFinishDown();
        $this->printStack();
    }

    private function liveOrStack($line)
    {
        if($this->config->isConsoleLive())
            echo $line;
        else
            $this->printStack[] = $line;
    }

    private function printStack()
    {
        if( ! $this->config->isConsoleLive())
            foreach ($this->printStack as $line)
            {
                echo $line;
            }
    }

    private function printStartUp()
    {
        // Get size
        $this->setCommandSize();
        $this->clearScreen();

        // Live indication
        $liveIndication = ($this->config->isConsoleLive()) ? terminal_style(' LIVE ', 'gray', 'red') : '';

        // Query log indication
        $queryLogIndication = '';
        if($this->config->queryLogState === true)
            $queryLogIndication = terminal_style(' QUERY ', 'gray', 'black');
        elseif($this->config->queryLogState === false)
            $queryLogIndication = terminal_style(' QUERY NOT ACTIVE ', 'gray', 'yellow', 'bold');

        // Execution time
        $textExecutionTime = (ini_get('max_execution_time') > 1) ? ini_get('max_execution_time') . ' sec' : 'unlimited';

        // Print art
        $this->liveOrStack(PHP_EOL
            . " " . terminal_style('     PHP PERFORMANCE TOOL     ', null, 'gray') . $queryLogIndication . $liveIndication . PHP_EOL
            . " Created by B. van Hoekelen version " .  PerformanceHandler::VERSION . " PHP v" . phpversion() . PHP_EOL
            . " Max memory " . ini_get("memory_limit") . ", max execution time " . $textExecutionTime . " on " . date('Y-m-d H:i:s') . PHP_EOL
            . PHP_EOL);

        // Print head
        $this->printHeadLine();

    }

    private function printHeadLine()
    {
        $this->liveOrStack(
            str_pad("   Label", $this->cellWightLabel)
            . " " . str_pad('Time', $this->cellWightResult, ' ', STR_PAD_BOTH)
            . " " . str_pad('Memory', $this->cellWightResult, ' ', STR_PAD_BOTH)
            . " " . str_pad('Peak', $this->cellWightResult, ' ', STR_PAD_BOTH) . PHP_EOL
            . str_repeat("-", $this->commandLineWidth - 1) . PHP_EOL);
    }

    private function printFinishDown()
    {
        $calculateTotalHolder = $this->calculate->totalTimeAndMemory($this->pointStack);

        if($this->commandLineWidth > 80)
            $a = str_pad("   Total " . (count($this->pointStack) - 2) . " taken ", $this->cellWightLabel - 15) . date('m-d H:i:s') . ' ';
        else
            $a = str_pad("   Total " . (count($this->pointStack) - 2) . " taken ", $this->cellWightLabel);

        $this->liveOrStack( str_repeat("-", $this->commandLineWidth - 1) . PHP_EOL
            . $a
            . " " . $this->formatter->stringPad( $this->formatter->timeToHuman( $calculateTotalHolder->totalTime ) . ' ', $this->cellWightResult, ' ', STR_PAD_LEFT)
            . " " . str_pad( $this->formatter->memoryToHuman( $calculateTotalHolder->totalMemory ) . ' ', $this->cellWightResult, ' ', STR_PAD_LEFT)
            . " " . str_pad( $this->formatter->memoryToHuman( $calculateTotalHolder->totalMemoryPeak ) . ' ', $this->cellWightResult, ' ', STR_PAD_LEFT)
            . PHP_EOL
            . PHP_EOL);
    }

    private function setCommandSize()
    {
        $this->commandLineWidth = exec('tput cols');
        $this->commandLineHeight = exec('tput lines');

        if($this->commandLineWidth < 60)
            $this->commandLineWidth = 60;
        if ($this->commandLineWidth > 100)
            $this->commandLineWidth = 100;

        /*
         *  |<------------------------------- ( Terminal wight ) ---------------------------------->|
         *  | <---------------- (38 - wight) ---------------><---------------- 39 --------------->| | < terminal border
         *  |    Label                                       .    Time    .   Memory   .    Peak    |
         *  | ------------------------------------------------------------------------------------- |
         *  |  > Calibrate point long label text long labe.. |   <-12->   |   <-12->   |  <-11->    |
         *  |  > Task 1                                      | 9999.99 μs | 9999.99 KB | 9999.99 MB |
         *  | ------------------------------------------------------------------------------------- |
         *  |    Total 7 taken                                 9999.97 ms   9999.00 MB   9999.99 MB |
         *  |
         */

        $this->cellWightResult = 12;
        $this->cellWightLabel = $this->commandLineWidth - 40;
    }

    private function clearScreen()
    {
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            system('cls');
        else
            system('clear');
    }

    private function printPointNewLineMessage(Point $point)
    {
        if(count($point->getNewLineMessage()))
        {
            foreach ($point->getNewLineMessage() as $message)
            {
                $this->printMessage($message);
            }
        }
    }

    public function printMessage($message = null, $time = '-- ', $memory = '-- ', $peak = '-- ')
    {
        $this->liveOrStack(terminal_style(str_pad(mb_strimwidth( "   " . $message, 0, $this->cellWightLabel, '..'), $this->cellWightLabel)
            . ' ' . str_pad($time, $this->cellWightResult, ' ', STR_PAD_LEFT)
            . '|' . str_pad($memory, $this->cellWightResult, ' ', STR_PAD_LEFT)
            . '|' . str_pad($peak, $this->cellWightResult, ' ', STR_PAD_LEFT) , 'dark-gray'). PHP_EOL );
    }

    private function printPointQueryLogAsNewLineMessage(Point $point)
    {
        // View type resume
        if($this->config->getQueryLogView() == 'resume')
        {
            $infoArray = [];

            foreach ($point->getQueryLog() as $queryLogHolder) {
                $type = $queryLogHolder->queryType;

                if (isset($infoArray[$type])) {
                    $infoArray[$type]['count']++;
                    $infoArray[$type]['time'] = $infoArray[$type]['time'] + $queryLogHolder->time;
                } else {
                    $infoArray[$type]['count'] = 1;
                    $infoArray[$type]['time'] = $queryLogHolder->time;
                }
            }

            ksort($infoArray);

            foreach ($infoArray as $key => $item) {
                $this->printMessage('Database query ' . $key . ' ' . $item['count'] . 'x', $item['time'] . ' ms ');

            }
        }

        // View type full
        if($this->config->getQueryLogView() == 'full')
        {
            foreach ($point->getQueryLog() as $queryLogHolder) {
                $this->printMessage('Database query ' . $queryLogHolder->query, $queryLogHolder->time . ' ms ');

            }
        }
    }
}