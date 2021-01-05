<?php
/**
 * Class LogHelper
 * 作者: su
 * 时间: 2020/11/25 11:37
 * 备注: 日志类
 */

namespace App\Helper;


use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogHelper
{
    /** @var string 已有配置名 */
    const Group_Default = 'default';
    const Group_Http = 'http';
    const Group_Error = 'error';
    const Group_Print_Log = 'printLog'; // 默认使用

    /**
     * @var LoggerFactory
     */
    static $loggerFactory;

    /**
     * @var
     * @var array ['日志配置名' => ['logName' => <LoggerInterface>,] ]]
     */
    static $logger = [];

    /**
     * 写日志
     * @param        $group
     * @param        $logName
     * @param        $content
     * @param string $logLevel  日志级别
     * @param int    $recordNum 记录来源，记录来源条数 0不记录
     * @return null
     */
    public static function write($group, $logName, $content, $logLevel = LogLevel::INFO, $recordNum = 1)
    {
        if ($recordNum) {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1 + $recordNum);
            for ($i = 1; $i <= $recordNum; $i++) {
                $content = $content . PHP_EOL . $debug[$i]['file'] . '[' . $debug[$i]['line'] . ']';
            }
        }

        /** @var LoggerInterface $logger */
        $logger = null;
        if (!isset(self::$logger[$group][$logName])) {
            if (empty(self::$loggerFactory)) {
                self::$loggerFactory = ApplicationContext::getContainer()->get(LoggerFactory::class);
            }
            try {
                $logger                         = self::$loggerFactory->get($logName, $group);
                self::$logger[$group][$logName] = $logger;
            } catch (\Throwable $ex) {
                if (isset(self::$logger[self::Group_Error][self::Group_Error])) {
                    $logger = self::$logger[self::Group_Error][self::Group_Error];
                }
                $logger->error('不存在日志组[' . $group . ']，转存日志信息：' . $content);
                return null;
            }
        }
        $logger = self::$logger[$group][$logName];

        switch ($logLevel) {
            case LogLevel::ERROR:
                $logger->error($content);
                break;
            case LogLevel::WARNING:
                $logger->warning($content);
                break;
            case LogLevel::ALERT:
                $logger->alert($content);
                break;
            case LogLevel::INFO:
                $logger->info($content);
                break;
            default:
                $logger->info($content);
                break;
        }
        return true;
    }

    /**
     * 错误日志
     * @param string $content 打印内容
     * @param string $logName log名
     * @param string $group   日志组名
     * @param int   $recordNum 记录来源条数
     */
    public static function error($content, $logName = LogLevel::ERROR, $group = self::Group_Print_Log, $recordNum = 1)
    {
        self::write($group, $logName, $content, LogLevel::ERROR, $recordNum);
    }

    public static function info($content, $logName = LogLevel::INFO, $group = self::Group_Print_Log, $recordNum = 1)
    {
        self::write($group, $logName, $content, LogLevel::INFO, $recordNum);
    }

    public static function warning($content, $logName = LogLevel::WARNING, $group = self::Group_Print_Log, $recordNum = 1)
    {
        self::write($group, $logName, $content, LogLevel::WARNING, $recordNum);
    }

    public static function alert($content, $logName = LogLevel::ALERT, $group = self::Group_Print_Log, $recordNum = 1)
    {
        self::write($group, $logName, $content, LogLevel::ALERT, $recordNum);
    }

}