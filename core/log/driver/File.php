<?php
namespace core\log\driver;

/**
 * 本地化调试输出到文件
 */
class File
{
    protected $config = [
        'time_format' => ' c ',
        'file_size'   => 2097152,   //2M
        'path'        => LOG_PATH,
        'apart_level' => [],
    ];

    protected $writed = [];

    // 实例化并传入参数
    public function __construct($config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log = [])
    {
       
        $destination = $this->config['path'] . date('Ym') . DS . date('d') . '.log';

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        $info = '';
        foreach ($log as $type => $msg) {
            $level = '';
            if (!is_string($msg)) {
                $msg = var_export($msg, true);
            }
            $level .= '[ ' . $type . ' ] ' . $msg. "\r\n";
            $info .= $level;
        }
        if ($info) {
            return $this->write($info, $destination);
        }
        return true;
    }

    protected function write($message, $destination, $apart = false)
    {
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            rename($destination, dirname($destination) . DS . time() . '-' . basename($destination));
            $this->writed[$destination] = false;
        }

        if (empty($this->writed[$destination])) {
            //生产环境不记录详细耗资信息
            if (ENVIRONMENT !== 'production') {
                // 获取基本信息
                if (isset($_SERVER['HTTP_HOST'])) {
                    $current_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                } else {
                    $current_uri = "cmd:" . implode(' ', $_SERVER['argv']);
                }

                $runtime    = round(microtime(true) - START_TIME, 10);
                $reqs       = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
                $time_str   = ' [运行时间：' . number_format($runtime, 6) . 's][吞吐率：' . $reqs . 'req/s]';
                $memory_use = number_format((memory_get_usage() - START_MEM) / 1024, 2);
                $memory_str = ' [内存消耗：' . $memory_use . 'kb]';
                $file_load  = ' [文件加载：' . count(get_included_files()) . ']';

                $message = '[ info ] ' . $current_uri . $time_str . $memory_str . $file_load . "\r\n" . $message;
            }
            $now     = date($this->config['time_format']);
            $server  = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '0.0.0.0';
            $remote  = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
            $method  = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
            $uri     = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $message = "---------------------------------------------------------------\r\n[{$now}] {$server} {$remote} {$method} {$uri}\r\n" . $message;

            $this->writed[$destination] = true;
        }
        //写文件
        return error_log($message, 3, $destination);
    }

}
