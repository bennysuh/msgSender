<?php  
namespace Lib;
    /** 
     * 日志处理类 
     *  
     * @since alpha 0.0.1 
     * @date 2016.01.14 
     * @author xx 
     *  
     */
class Log
{           
        //单例模式  
        private static $instance    = NULL;  
        //文件句柄  
        private static $handle      = NULL;  
        //日志开关  
        private $log_switch     = NULL;  
        //日志相对目录  
        private $log_file_path      = NULL;  
        //日志文件最大长度，超出长度重新建立文件  
        private $log_max_len        = NULL;  
        //日志文件前缀,入 log_0  
        private $log_file_pre       = 'log_';  
       
               
        /** 
         * 构造函数 
         *  
         * @since alpha 0.0.1 
         * @date 2014.02.04 
         * @author genialx 
         */  
        protected function __construct(){//注意：以下是配置文件中的常量，请读者自行更改
            $this->log_file_path     = './Log/'; 
            $this->log_switch     = true;
            $this->log_max_len    = 10 * 1024 * 1024 ; //10M 
        }  
           
        /** 
         * 单利模式 
         *  
         * @since alpha 0.0.1 
         * @date 2014.02.04 
         * @author genialx 
         */  
        public static function get_instance(){  
            if(!self::$instance instanceof self){  
                self::$instance = new self;  
            }  
            return self::$instance;  
        }  
           
        /** 
         *  
         * 日志记录 
         *  
         * @param int $type  0 -> 记录(THING LOG) / 1 -> 错误(ERROR LOG) 
         * @param string $desc 
         * @param string $time 
         *  
         * @since alpha 0.0.1 
         * @date 2014.02.04 
         * @author genialx 
         *  
         */  
        public function log($type,$desc){  
            if($this->log_switch){
                $time = date('Y-n-j H:m:s');
                if(self::$handle == NULL){  
                    $filename = $this->log_file_pre . $this->get_max_log_file_suf(); 
                    self::$handle = fopen($this->log_file_path . $filename, 'a');  
                }  
                switch($type){  
                    case 0:  
                        @fwrite(self::$handle, 'THING LOG:' . ' ' . $desc . ' ' . $time . chr(13));  
                        break;  
                    case 1:  
                        @fwrite(self::$handle, 'ERROR LOG:' . ' ' . $desc . ' ' . $time . chr(13));  
                        break;  
                    default:  
                        @fwrite(self::$handle, 'THING LOG:' . ' ' . $desc . ' ' . $time . chr(13));  
                        break;  
                }  
                   
            }  
        }  
           
        /** 
         * 获取当前日志的最新文档的后缀 
         *  
         * @since alpha 0.0.1 
         * @date 2014.02.04 
         * @author genialx 
         */  
        private function get_max_log_file_suf(){  
            $log_file_suf = null;  
            if(is_dir($this->log_file_path)){  
                if($dh = opendir($this->log_file_path)){  
                    while(($file = readdir($dh)) != FALSE){  
                        if($file != '.' && $file != '..'){  
                            if(filetype( $this->log_file_path . $file) == 'file'){  
                                $rs = split('_', $file);  
                                if($log_file_suf < $rs[1]){  
                                    $log_file_suf = $rs[1];  
                                }  
                            }  
                        }  
                    }  
                       
                    if($log_file_suf == NULL){  
                        $log_file_suf = 0;  
                    }  
                    //截断文件  
                    if( file_exists($this->log_file_path . $this->log_file_pre . $log_file_suf) && filesize($this->log_file_path . $this->log_file_pre . $log_file_suf) >= $this->log_max_len){  
                        $log_file_suf = intval($log_file_suf) + 1;  
                    }  
                       
                    return $log_file_suf;  
                }     
            }  
               
            return 0;  
               
        }  
           
        /** 
         * 关闭文件句柄 
         *  
         * @since alpha 0.0.1 
         * @date 2014.02.04 
         * @author genialx 
         */  
        public function close(){
            @fclose(self::$handle);  
        }  
}  
?>