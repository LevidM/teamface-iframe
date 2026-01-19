<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>百度弹窗过滤器--工总内部使用</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="url"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="url"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
            display: none;
        }
        
        .result.show {
            display: block;
        }
        
        .result-item {
            margin-bottom: 15px;
        }
        
        .result-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .result-value {
            word-break: break-all;
            color: #666;
        }
        
        .short-url {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .short-url input {
            flex: 1;
            border: none;
            padding: 5px;
            font-size: 14px;
            background: transparent;
        }
        
        .copy-btn {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
        
        .qr-code img {
            max-width: 200px;
            border: 4px solid white;
            border-radius: 8px;
        }
        
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message.show {
            display: block;
        }
        
        .message.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .message.success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>百度弹窗过滤器</h1>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">生成过滤弹窗的新链接</p>
        
        <div id="message" class="message"></div>
        
        <form id="linkForm">
            <div class="form-group">
                <label for="url">输入需要过滤的链接：</label>
                <input type="url" id="url" name="url" placeholder="https://example.com/page" required>
            </div>
            <button type="submit" id="submitBtn">生成链接</button>
        </form>
        
        <div id="result" class="result">
            <div class="result-item">
                <div class="result-label">链接地址：</div>
                <div class="short-url">
                    <input type="text" id="shortUrl" readonly>
                    <button class="copy-btn" onclick="copyUrl()">复制</button>
                </div>
            </div>
            <div class="result-item">
                <div class="result-label">原始链接：</div>
                <div class="result-value" id="originalUrl"></div>
            </div>
            <div class="qr-code">
                <div id="qrContainer"></div>
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('linkForm');
        const result = document.getElementById('result');
        const message = document.getElementById('message');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const url = document.getElementById('url').value.trim();
            if (!url) {
                showMessage('请输入链接', 'error');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '生成中...';
            hideMessage();
            
            try {
                const response = await fetch('api.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ url: url })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('shortUrl').value = data.short_url;
                    document.getElementById('originalUrl').textContent = data.original_url;
                    
                    // 生成二维码（使用后端生成的二维码）
                    const qrContainer = document.getElementById('qrContainer');
                    qrContainer.innerHTML = '<img src="qr.php?code=' + data.short_code + '" alt="二维码">';
                    
                    result.classList.add('show');
                    showMessage('链接生成成功！', 'success');
                } else {
                    showMessage(data.message || '生成失败', 'error');
                }
            } catch (error) {
                showMessage('网络错误，请重试', 'error');
                console.error(error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '生成链接';
            }
        });
        
        function showMessage(text, type) {
            message.textContent = text;
            message.className = 'message show ' + type;
        }
        
        function hideMessage() {
            message.classList.remove('show');
        }
        
        function copyUrl() {
            const input = document.getElementById('shortUrl');
            input.select();
            document.execCommand('copy');
            showMessage('链接已复制到剪贴板', 'success');
            setTimeout(hideMessage, 2000);
        }
    </script>
</body>
</html>

