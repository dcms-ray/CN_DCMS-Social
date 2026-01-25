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

/**
 * 获取客户端 IP 和 User-Agent
 * @return array
 */
class ClientDetails
{
	protected $set;
	protected $db;

	public function __construct($set, $db) {
		$this->set = $set;
		$this->db = $db;
	}

	/**
	 * 获取客户端 IP 和 User-Agent
	 * @return array
	 */
	public function getClientDetails() {
		$cdnIpRanges = $this->getCdnIpRanges();
		$ip = $this->getClientIp($cdnIpRanges);
		$ua = $this->getUserAgent();

		return [
			'ip' => $ip,
			'ua' => $ua
		];
	}

	/**
	 * 从数据库获取 CDN IP 范围
	 * @return array
	 */
	protected function getCdnIpRanges() {
		// 查询 'cdn_ips' 表中的所有数据
		$result = $this->db->queryAll("SELECT `ip_range` FROM `cdn_ips`"); 

		// 如果没有数据，返回空数组
		if (count($result) > 0) {
			return array_map(function ($cidr) {
				return IPLib\Factory::parseRangeString($cidr['ip_range']);
			}, $result);
		}
		return [];
	}

	/**
	 * 检查一个IP是否在特定IP范围内
	 * @param string $ip
	 * @param array $ranges
	 * @return bool
	 */
	protected function isIpInRange($ip, $ranges) {
		$ipAddress = IPLib\Factory::addressFromString($ip);
		foreach ($ranges as $range) {
			if ($range->contains($ipAddress)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 获取客户端 IP 地址
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getClientIp($cdnIpRanges) {
		$ip = '';
		switch ($this->set['get_ip_from_header']) {
			case 'Forwarded':
				$ip = $this->getForwardedIp($cdnIpRanges);
				break;
			case 'X-Forwarded-For':
				$ip = $this->getXForwardedForIp($cdnIpRanges);
				break;
			case 'X-Real-IP':
				$ip = $this->getXRealIp($cdnIpRanges);
				break;
			case 'CF-Connecting-IP':
				$ip = $this->getCfConnectingIp($cdnIpRanges);
				break;
			case 'True-Client-IP':
				$ip = $this->getTrueClientIp($cdnIpRanges);
				break;
			case 'disabled':
			default:
				$ip = $_SERVER['REMOTE_ADDR'];
				break;
		}
		return $ip;
	}

	/**
	 * 处理 'Forwarded' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getForwardedIp($cdnIpRanges) {
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
	protected function getXForwardedForIp($cdnIpRanges) {
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
	protected function getXRealIp($cdnIpRanges) {
		return !empty($_SERVER['HTTP_X_REAL_IP']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)
			? $_SERVER['HTTP_X_REAL_IP'] 
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 处理 'CF-Connecting-IP' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getCfConnectingIp($cdnIpRanges) {
		return !empty($_SERVER['HTTP_CF_CONNECTING_IP']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)
			? $_SERVER['HTTP_CF_CONNECTING_IP'] 
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 处理 'True-Client-IP' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getTrueClientIp($cdnIpRanges) {
		return !empty($_SERVER['HTTP_TRUE_CLIENT_IP']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)
			? $_SERVER['HTTP_TRUE_CLIENT_IP'] 
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 获取 User-Agent
	 * @return string
	 */
	protected function getUserAgent() {
		$ua = 'N/A';
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$result = UAParser\Parser::create()->parse($ua);
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

/**
 * 使用方式
 * 
 * 获取 IP 和 User-Agent
 * $ip = $clientDetails['ip'];
 * $ua = $clientDetails['ua'];
 */
$clientDetails = (new ClientDetails($set, $db))->getClientDetails();
$ua = $clientDetails['ua'];
$ip = $clientDetails['ip'];