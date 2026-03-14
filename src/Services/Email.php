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

namespace GuGuan123\dcms\Services;

class Email
{
	public function __construct(
		private array $set, 
		private \GuGuan123\dcms\Database $db
	) {}

	/**
	 * 发送邮件的函数
	 *
	 * @param string $subject 邮件主题
	 * @param string $body 邮件内容（HTML格式）
	 * @param string $recipientEmail 收件人邮箱
	 * @param string|null $recipientName 收件人姓名（可选）
	 * @return array 发送邮件的结果，包含状态和消息
	 */
	public function send($subject, $body, $recipientEmail, $recipientName = null) {
		if ($this->setset['mail_transport_type'] == 'smtp') {
			if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
				// 创建 PHPMailer 实例
				$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
				try {
					// 服务器设置
					$mail->isSMTP();
					$mail->setLanguage('zh_cn');
					$mail->Host = $this->set['smtp_host'];											// SMTP 服务器（替换为你自己的 SMTP 服务器）
					$mail->SMTPAuth = ($this->set['smtp_auth'] == '1' ? true : false);				// 启用 SMTP 验证
					$mail->Username = $this->set['smtp_username'];									// SMTP 用户名
					$mail->Password = $this->set['smtp_password'];									// SMTP 密码
					if ($this->set['smtp_secure'] == 'starttls') {
						$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // 使用显式 TLS 加密
					} elseif ($set['smtp_secure'] == 'tls') {
						$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;    // 使用隐式 TLS 加密
					} else {
						$mail->SMTPSecure = NULL;                                               // 不加密，使用纯文本传输
					}
					$mail->Port = (int)$this->set['smtp_port'];										// SMTP 端口号

					$mail->CharSet = 'UTF-8';													// 设置邮件的字符集为 UTF-8

					// 发件人设置
					$mail->setFrom($this->set['set_email_from'], $this->set['set_email_from_name'] ?? '');
					$mail->addReplyTo($this->set['set_email_reply_to'], $this->set['set_email_reply_to_name'] ?? '');

					// 收件人设置
					$mail->addAddress($recipientEmail, $recipientName);

					// 内容设置
					$mail->isHTML(true);
					$mail->Subject = $subject;
					$mail->Body = $body;

					// 发送邮件
					$mail->send();
					return true;

				} catch (\PHPMailer\PHPMailer\Exception $e) {
					throw new \Exception($mail->ErrorInfo);
				}
			} else {
				throw new \Exception('邮件发送失败: PHPMailer 未安装，无法使用 SMTP 发送电子邮件');
			}
		} else {
			mail($recipientEmail, '=?utf-8?B?' . base64_encode($subject), $body);
		}
	}
}
