<?php

namespace GuGuan123\dcms\Core;

/**
 * 系统设置服务类
 * 负责加载、获取、修改和保存系统配置
 */
class Settings {
	private static ?Settings $instance = null;
	private array $settings = [];
	private array $dynamicSettings = [];
	private string $defaultFile;
	private string $dynamicFile;

	/**
	 * 获取单例实例
	 */
	public static function getInstance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->defaultFile = dirname(__DIR__, 2) . '/sys/dat/default.ini';
		$this->dynamicFile = dirname(__DIR__, 2) . '/sys/dat/settings.php';

		$this->load();
	}

	/**
	 * 加载所有设置
	 */
	public function load(): void {
		// 1. 加载默认配置
		$default = [];
		$replace = [];
		if (file_exists($this->defaultFile)) {
			$ini = parse_ini_file($this->defaultFile, true);
			$default = $ini['DEFAULT'] ?? [];
			$replace = $ini['REPLACE'] ?? [];
		}

		// 2. 加载动态配置
		$this->dynamicSettings = [];
		if (file_exists($this->dynamicFile)) {
			$this->dynamicSettings = require $this->dynamicFile;
		}

		// 3. 合并配置 (顺序：默认 < 动态 < 覆盖)
		// 注意：原逻辑是 array_merge($set_default, $set_dynamic, $set_replace)
		$this->settings = array_merge($default, $this->dynamicSettings, $replace);
	}

	/**
	 * 获取设置项
	 * 
	 * @param string $key 键名
	 * @param mixed $default 默认值
	 * @return mixed
	 */
	public function get(string $key, $default = null) {
		return $this->settings[$key] ?? $default;
	}

	/**
	 * 设置动态项（仅修改内存中的值，需调用 save 才能保存）
	 * 
	 * @param string $key 键名
	 * @param mixed $value 值
	 */
	public function set(string $key, $value): void {
		$this->settings[$key] = $value;
		$this->dynamicSettings[$key] = $value;
	}

	/**
	 * 保存动态设置到文件
	 * 
	 * @return bool 是否成功
	 */
	public function save(): bool {
		// 移除不该保存的临时变量（如 web）
		$toSave = $this->dynamicSettings;
		unset($toSave['web']);

		$content = "<?php\n/**\n * DCMS System Settings\n * Generated at: " . date('Y-m-d H:i:s') . "\n */\nreturn " . var_export($toSave, true) . ";\n";
		
		if (file_put_contents($this->dynamicFile, $content) !== false) {
			@chmod($this->dynamicFile, 0777);
			return true;
		}
		return false;
	}

	/**
	 * 获取所有配置数组
	 */
	public function getAll(): array {
		return $this->settings;
	}

	/**
	 * 检查是否已安装（是否存在动态配置文件）
	 */
	public function isInstalled(): bool {
		return file_exists($this->dynamicFile);
	}
}
