<?php
	declare(strict_types=1);

	// API LINK
	const QR_API_URL = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=';

	function e(string $value): string {
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}

	function generateQrUrl(string $payload): string {
		return QR_API_URL . urlencode($payload);
	}

	function buildQrPayload(string $type, array $input): ?string {
		return match ($type) {
			'text'  => trim($input['text'] ?? ''),
			
			'url'   => filter_var($input['url'] ?? '', FILTER_VALIDATE_URL) ?: null,
			
			'wifi'  => sprintf(
				'WIFI:T:%s;S:%s;P:%s;;',
				$input['encryption'] ?? '',
				$input['ssid'] ?? '',
				$input['password'] ?? ''
			),
			
			'vcard' => sprintf(
				"BEGIN:VCARD\nVERSION:3.0\nN:%s;%s\nFN:%s %s\nTEL:%s\nEMAIL:%s\nEND:VCARD",
				$input['lname'] ?? '',
				$input['fname'] ?? '',
				$input['fname'] ?? '',
				$input['lname'] ?? '',
				$input['phone'] ?? '',
				$input['email'] ?? ''
			),
			default => null
		};
	}

	$qrImageUrl = null;
	$type = $_POST['type'] ?? '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && $type) {
		$payload = buildQrPayload($type, $_POST);
		if (!empty($payload)) {
			$qrImageUrl = generateQrUrl($payload);
		}
	}
?>
<!DOCTYPE html>
<html lang="de">
	<head>
		<meta charset="UTF-8">
		<title>snippet-qrcode</title>
		<script src="https://unpkg.com/@zxing/library@latest"></script>
		<style>
			img {
				margin-top: 10px;
				border: 1px solid #ccc;
			}

			.result {
				padding: 10px;
				background: #f6f6f6;
				border: 1px solid #ccc;
				word-break: break-all;
				min-width: 100px;
				max-width: 200px;
			}
		</style>
	</head>
	<body>

		<h1>QR-Code Generator</h1>

		<section class="section">
			<form method="post" novalidate>
				<select name="type" onchange="this.form.submit()">
					<option value=""> --- Choose Type --- </option>
					<option value="text" <?= $type==='text'?'selected':'' ?>>Text</option>
					<option value="url" <?= $type==='url'?'selected':'' ?>>URL (Website)</option>
					<option value="wifi" <?= $type==='wifi'?'selected':'' ?>>WLAN</option>
					<option value="vcard" <?= $type==='vcard'?'selected':'' ?>>vCard (Add contact)</option>
				</select>

				<?php if($type==='text'): ?>
					<div class="result">
						<input name="text" placeholder="Any Text" value="<?= e($_POST['text'] ?? '') ?>">
					</div>
				<?php elseif($type==='url'): ?>
					<div class="result">
						<input name="url" placeholder="https://mabgl.com" value="<?= e($_POST['url'] ?? '') ?>">
					</div>
				<?php elseif($type==='wifi'): ?>
					<div class="result">
						<input name="ssid" placeholder="SSID" value="<?= e($_POST['ssid'] ?? '') ?>">
						<input name="password" placeholder="Passwort" value="<?= e($_POST['password'] ?? '') ?>">
						<select name="encryption">
							<option value="WPA" <?= ($_POST['encryption']??'')==='WPA'?'selected':'' ?>>WPA/WPA2</option>
							<option value="WEP" <?= ($_POST['encryption']??'')==='WEP'?'selected':'' ?>>WEP</option>
							<option value="" <?= ($_POST['encryption']??'')===''?'selected':'' ?>>Open</option>
						</select>
					</div>
				<?php elseif($type==='vcard'): ?>
					<div class="result">
						<input name="fname" placeholder="Firstname" value="<?= e($_POST['fname'] ?? '') ?>">
						<input name="lname" placeholder="Lastname" value="<?= e($_POST['lname'] ?? '') ?>">
						<input name="phone" placeholder="Phone Number" value="<?= e($_POST['phone'] ?? '') ?>">
						<input name="email" placeholder="E-Mail" value="<?= e($_POST['email'] ?? '') ?>">
					</div>
				<?php endif; ?>

				<?php if($type): ?>
					<br><button type="submit">Generate</button><br><br>
				<?php endif; ?>
			</form>

			<?php if ($qrImageUrl): ?>
				<h3>Your QR-Code</h3>
				<img src="<?= e($qrImageUrl) ?>" alt="QR-Code">
			<?php endif; ?>
		</section>

		<hr>

		<h1>QR-Code Scanner</h1>

		<section class="section">
			<input type="file" id="fileInput" accept="image/*">
			<div class="result" id="scanResult">&nbsp;</div>
		</section>
		
		<br><br><br>
				
		<script>
			const fileInput = document.getElementById('fileInput');
			const resultBox = document.getElementById('scanResult');
			const reader = new ZXing.BrowserQRCodeReader();

			fileInput.addEventListener('change', async (e) => {
				const file = e.target.files[0];
				if(!file) return;
				const img = document.createElement('img');
				img.src = URL.createObjectURL(file);
				img.onload = async () => {
					try {
						const result = await reader.decodeFromImageElement(img);
						resultBox.textContent = result.text;
					} catch {
						resultBox.textContent = '❌ That is not a QR-Code!';
					}
				};
			});
		</script>
	</body>
</html>
