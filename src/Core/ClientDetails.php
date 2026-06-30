<?php
/*
 * MIT License
 * 
 * Copyright (c) 2025 GuGuan123
 * 
 * 本软件基于 MIT 许可证发布。具体许可条款如下：
 * 
 * 允许在本软件及其附带文档文件（以下简称“软件”）的基础上进行修改、复制、分发及/或销售，
 * 且在提供软件的副本时，需附上此许可证声明和版权声明。
 * 
 * 本软件按“原样”提供，不作任何形式的明示或暗示的担保，包括但不限于对适销性、适合某一特定用途的担保。
 * 在任何情况下，无论是在合同诉讼、侵权或其他诉讼中，作者或版权持有者对因使用本软件或其他交易的结果
 * 所产生的任何索赔、损害或其他责任不承担任何责任。
 * 
 * 你可以在 https://choosealicense.com/licenses/mit/ 查看详细的 MIT 原始许可证条款。
 */

namespace GuGuan123\dcms\Core;

/**
 * 获取客户端 IP 和 User-Agent
 * @return array
 */
class ClientDetails
{
	protected \GuGuan123\dcms\Core\Settings $set;
	protected \GuGuan123\dcms\Core\Database $db;

	/** @var array|null CDN IP 范围缓存 */
	protected ?array $cdnIpRanges = null;

	public function __construct() {
		$this->set = \GuGuan123\dcms\Core\Settings::getInstance();
		$this->db = \GuGuan123\dcms\Core\Database::getInstance();
	}

	/**
	 * 获取客户端 IP 地址
	 * @return string
	 */
	public function getClientIp(): string {
		if ($this->cdnIpRanges === null) {
			$rawRanges = $this->db->queryAll("SELECT `ip_range` FROM `cdn_ips`") ?: [];
			$this->cdnIpRanges = array_map(fn($item) => \IPLib\Factory::parseRangeString($item['ip_range']), $rawRanges);
		}

		return match ($this->set->get('get_ip_from_header') ?? 'disabled') {
			'Forwarded'        => $this->getForwardedIp($this->cdnIpRanges),
			'X-Forwarded-For'  => $this->getXForwardedForIp($this->cdnIpRanges),
			'X-Real-IP'        => $this->getXRealIp($this->cdnIpRanges),
			'CF-Connecting-IP' => $this->getCfConnectingIp($this->cdnIpRanges),
			'True-Client-IP'   => $this->getTrueClientIp($this->cdnIpRanges),
			default => $_SERVER['REMOTE_ADDR'],
		};
	}

	/**
	 * 检查一个IP是否在特定IP范围内
	 * @param string $ip
	 * @param array $ranges
	 * @return bool
	 */
	protected function isIpInRange(string $ip, array $ranges): bool {
		$ipAddress = \IPLib\Factory::addressFromString($ip);
		// IP 格式不对直接返回 false
		if (!$ipAddress) return false;
		foreach ($ranges as $range) {
			if ($range && $range->contains($ipAddress)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 处理 'Forwarded' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getForwardedIp(array $cdnIpRanges): string {
		if (!empty($_SERVER['HTTP_FORWARDED']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)) {
			foreach (array_map('trim', explode(',', $_SERVER['HTTP_FORWARDED'])) as $part) {
				if (stripos($part, 'for=') !== false) {
					return trim(str_ireplace('for=', '', $part));
				}
			}
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 处理 'X-Forwarded-For' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getXForwardedForIp(array $cdnIpRanges): string {
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)) {
			foreach (array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])) as $ip) {
				if ($this->isIpInRange($ip, $cdnIpRanges)) {
					continue;
				}
				return $ip;
			}
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 处理 'X-Real-IP' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getXRealIp(array $cdnIpRanges): string {
		return !empty($_SERVER['HTTP_X_REAL_IP']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)
			? $_SERVER['HTTP_X_REAL_IP'] 
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 处理 'CF-Connecting-IP' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getCfConnectingIp(array $cdnIpRanges): string {
		return !empty($_SERVER['HTTP_CF_CONNECTING_IP']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)
			? $_SERVER['HTTP_CF_CONNECTING_IP'] 
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 处理 'True-Client-IP' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getTrueClientIp(array $cdnIpRanges): string {
		return !empty($_SERVER['HTTP_TRUE_CLIENT_IP']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)
			? $_SERVER['HTTP_TRUE_CLIENT_IP'] 
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 获取 User-Agent
	 * @return string
	 */
	public function getUserAgent(): string {
		$ua = 'N/A';
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$result = \UAParser\Parser::create()->parse($ua);
			if (isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) && stripos($ua, 'Opera') !== false && false) {
				$ua_om = preg_replace('#[^a-z_\. 0-9\-]#iu', '', strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']));
				$ua = $result->toString() . '(' . $ua_om . ')';
			} else {
				$ua = $result->toString();
			}
		}
		return $ua;
	}
}
