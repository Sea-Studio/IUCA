<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @copyright © 瀚海云创IDC信息查询平台 及 瀚海云科技
 * 
 * 此源代码的使用受MPL 2.0许可证的约束。
 * 许可证的完整文本可以在 https://www.mozilla.org/en-US/MPL/2.0/ 找到。
 */
class EmailService {
    /** @var array 邮件配置 */
    private $config;
    /** @var \PHPMailer\PHPMailer\PHPMailer 邮件发送实例 */
    private $mail;
    /** @var bool 是否启用日志 */
    private $enableLogging;
    /** @var string 日志文件路径 */
    private $logFile;

    /**
     * 构造函数
     * @param array $config 邮件配置
     * @param bool $enableLogging 是否启用日志
     */
    public function __construct(array $config = [], bool $enableLogging = true) {
        // 加载默认配置
        $this->config = $config ?: $this->loadDefaultConfig();
        $this->enableLogging = $enableLogging;
        $this->logFile = dirname(__DIR__) . '/logs/email_' . date('Y-m-d') . '.log';

        // 初始化PHPMailer
        $this->initPHPMailer();
    }

    /**
     * 加载默认配置
     * @return array 配置数组
     */
    private function loadDefaultConfig(): array {
        // 尝试加载配置文件
        $configFile = __DIR__ . '/email_config.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }

        // 默认配置
        return [
            'host' => getenv('SMTP_HOST') ?: '',
            'port' => getenv('SMTP_PORT') ?: 587,
            'username' => getenv('SMTP_USERNAME') ?: '',
            'password' => getenv('SMTP_PASSWORD') ?: '',
            'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            'from_email' => getenv('SMTP_FROM_EMAIL') ?: '',
            'from_name' => getenv('SMTP_FROM_NAME') ?: '瀚海云创IDC查询',
            'debug' => false
        ];
    }

    /**
     * 初始化PHPMailer
     */
    private function initPHPMailer() {
        // 引入PHPMailer类
        require_once __DIR__ . '/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/phpmailer/SMTP.php';
        require_once __DIR__ . '/phpmailer/Exception.php';

        $this->mail = new \PHPMailer\PHPMailer\PHPMailer($this->config['debug'] ?? false);

        // 服务器设置
        $this->mail->isSMTP();
        $this->mail->Host = $this->config['host'];
        $this->mail->Port = $this->config['port'];
        $this->mail->SMTPSecure = !empty($this->config['encryption']) ? $this->config['encryption'] : '';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->config['username'];
        $this->mail->Password = $this->config['password'];

        // 发件人
        $fromEmail = !empty($this->config['from_email']) ? $this->config['from_email'] : $this->config['username'];
        $fromName = !empty($this->config['from_name']) ? $this->config['from_name'] : '瀚海云创IDC查询';
        $this->mail->setFrom($fromEmail, $fromName);

        // 设置字符集
        $this->mail->CharSet = 'UTF-8';
    }

    /**
     * 发送邮件
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param bool $isHtml 是否为HTML邮件
     * @param array $attachments 附件列表
     * @return array ['success' => bool, 'message' => string]
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = false, array $attachments = []): array {
        try {
            // 检查配置
            if (empty($this->config['host']) || empty($this->config['username']) || empty($this->config['password'])) {
                $message = '邮件配置不完整，请检查email_config.php文件';
                $this->log('错误: ' . $message);
                return ['success' => false, 'message' => $message];
            }

            // 验证邮箱格式
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $message = '无效的邮箱地址: ' . $to;
                $this->log('错误: ' . $message);
                return ['success' => false, 'message' => '请输入有效的邮箱地址'];
            }

            // 清空之前的收件人
            $this->mail->clearAddresses();

            // 添加收件人
            $this->mail->addAddress($to);

            // 添加附件
            foreach ($attachments as $filePath => $fileName) {
                if (file_exists($filePath)) {
                    $this->mail->addAttachment($filePath, $fileName);
                } else {
                    $this->log('警告: 附件不存在 - ' . $filePath);
                }
            }

            // 设置邮件内容
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->isHTML($isHtml);

            // 发送邮件
            $result = $this->mail->send();

            if ($result) {
                $message = '邮件发送成功';
                $this->log('成功: ' . $message . ' - 收件人: ' . $to . ' - 主题: ' . $subject);
                return ['success' => true, 'message' => $message];
            } else {
                $message = '发送邮件失败: ' . $this->mail->ErrorInfo;
                $this->log('错误: ' . $message);
                return ['success' => false, 'message' => $message];
            }
        } catch (\Exception $e) {
            $message = '发送邮件时发生错误: ' . $e->getMessage();
            $this->log('错误: ' . $message);
            return ['success' => false, 'message' => '发送邮件时发生错误，请稍后再试'];
        }
    }

    /**
     * 发送验证码邮件
     * @param string $email 收件人邮箱
     * @param string $captcha 验证码
     * @param int $expireMinutes 验证码有效期（分钟）
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendVerificationCode(string $email, string $captcha, int $expireMinutes = 10): array {
        $subject = '瀚海云创IDC查询 - 验证码';
        $body = "您的验证码是: $captcha\r\n\r\n此验证码有效期为$expireMinutes分钟，请尽快使用。\r\n\r\n如果您没有请求此验证码，请忽略此邮件。";

        return $this->send($email, $subject, $body, false);
    }

    /**
     * 发送通知邮件
     * @param string $email 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $content 通知内容
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendNotification(string $email, string $subject, string $content): array {
        $body = "尊敬的用户：\r\n\r\n$content\r\n\r\n此致\r\n瀚海云创IDC查询平台";

        return $this->send($email, $subject, $body, false);
    }

    /**
     * 记录日志
     * @param string $message 日志消息
     */
    private function log(string $message) {
        if (!$this->enableLogging) {
            return;
        }

        // 确保日志目录存在
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 记录日志
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * 获取最后一条日志
     * @return string|null 最后一条日志消息
     */
    public function getLastLog() {
        if (!file_exists($this->logFile)) {
            return null;
        }

        $logs = file($this->logFile);
        return end($logs) ?: null;
    }
}
?>