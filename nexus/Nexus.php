<?php
namespace Nexus;

final class Nexus
{
    private string $requestId;

    private int $logSequence = 0;

    private float $startTimestamp;

    private string $script;

    private string $platform;

    private static bool $booted = false;

    private static ?Nexus $instance = null;

    const PLATFORM_USER = 'user';
    const PLATFORM_ADMIN = 'admin';
    const PLATFORM_TRACKER = 'tracker';
    const PLATFORMS = [self::PLATFORM_USER, self::PLATFORM_ADMIN, self::PLATFORM_TRACKER];

    private function __construct()
    {

    }

    private function __clone()
    {

    }

    public static function instance()
    {
        return self::$instance;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getStartTimestamp(): float
    {
        return $this->startTimestamp;
    }


    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getScript(): string
    {
        return $this->script;
    }

    public function getLogSequence(): int
    {
        return $this->logSequence;
    }

    public function isPlatformValid(): bool
    {
        return in_array($this->platform, self::PLATFORMS);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->platform == self::PLATFORM_ADMIN;
    }

    public function isPlatformUser(): bool
    {
        return $this->platform == self::PLATFORM_USER;
    }

    public function isScriptAnnounce(): bool
    {
        return $this->script == 'announce';
    }

    public function incrementLogSequence()
    {
        $this->logSequence++;
    }

    private function runningInOctane(): bool
    {
        if (defined('RUNNING_IN_OCTANE') && RUNNING_IN_OCTANE) {
            return true;
        }
        return false;
    }

    private function generateRequestId(): string
    {
        $prefix = ($_SERVER['SCRIPT_FILENAME'] ?? '') . implode('', $_SERVER['argv'] ?? []);
        $prefix = substr(md5($prefix), 0, 4);
        // 4 + 23 = 27 characters, after replace '.', 26
        $requestId = str_replace('.', '', uniqid($prefix, true));
        $requestId .= bin2hex(random_bytes(3));
        return $requestId;
    }

    public static function boot()
    {
        if (self::$booted) {
            return;
        }
        $instance = new self();
        $instance->setStartTimestamp();
        $instance->setRequestId();
        $instance->setScript();
        $instance->setPlatform();
        self::$instance = $instance;
        self::$booted = true;
    }

    public static function flush()
    {
        self::$booted = false;

    }

    private function setRequestId()
    {
        $requestId = '';
        if ($this->runningInOctane()) {
            $request = request();
            $requestId = $request->server('request_id', $request->header('request_id', ''));
        } else {
            if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
                $requestId = $_SERVER['HTTP_X_REQUEST_ID'];
            } elseif (!empty($_SERVER['REQUEST_ID'])) {
                $requestId = $_SERVER['REQUEST_ID'];
            }
        }
        if (empty($requestId)) {
            $requestId = $this->generateRequestId();
        }
        $this->requestId = $requestId;
    }

    private function setScript()
    {
        if ($this->runningInOctane()) {
            $request = request();
            $script = $request->header('script_filename', '');
        } else {
            $script = strstr(basename($_SERVER['SCRIPT_FILENAME']), '.', true);
        }
        $this->script = $script;
    }

    private function setStartTimestamp()
    {
        $this->startTimestamp = microtime(true);
    }

    private function setPlatform()
    {
        if ($this->runningInOctane()) {
            $request = request();
            $platform = $request->header('platform', '');
        } else {
            $platform = $_SERVER['HTTP_PLATFORM'] ?? '';
        }
        $this->platform = $platform;
    }


}