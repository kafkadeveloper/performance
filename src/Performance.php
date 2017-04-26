<?php namespace Performance;

use Performance\Lib\Handlers\ConfigHandler;
use Performance\Lib\Handlers\PerformanceHandler;
use Performance\Lib\Handlers\PerformanceInterface;

class Performance implements PerformanceInterface
{
    /*
     * Create a performance instance
     */
    private static $performance;
    private static $bootstrap = false;

    public static function instance()
    {
        if( ! self::$performance)
            self::$performance = new PerformanceHandler();
        return self::$performance;
    }

    private static function enableTool()
    {
        $performance = self::instance();

        // Check DISABLE_TOOL
        if( ! $performance->config->isEnableTool())
            return false;

        // Check bootstrap
        if( ! self::$bootstrap)
        {
            $performance->bootstrap();
            self::$bootstrap = true;
        }

        return true;
    }

    /*
     * Set measuring point X
     *
     * @param string|null   $label
     * @return void
     */
    public static function point($label = null)
    {
        if( ! self::enableTool() )
            return;

        // Run
        self::$performance->point($label);
    }

    /*
     * Set message
     *
     * @param string|null   $message
     * @param boolean|null   $newLine
     * @return void
     */
    public static function message($message = null, $newLine = true)
    {
        if( ! self::enableTool() or ! $message)
            return;

        // Run
        self::$performance->message($message, $newLine);
    }


    /*
     * Finish measuring point X
     *
     * @param string|null   $label
     * @return void
     */
    public static function finish()
    {
        if( ! self::enableTool() )
            return;

        // Run
        self::$performance->finish();
    }

    /*
     * Return test results
     *
     * @return mixed
     */
    public static function results()
    {
        if( ! self::enableTool() )
            return;

        // Run
        self::$performance->results();
    }

    /*
     * Reset
     */
    public static function instanceReset()
    {
        // Run
        Config::instanceReset();
        self::$performance = null;
        self::$bootstrap = false;

    }
}