<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Sedang Update</title>
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/189/189689.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: #fff;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px 60px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
            animation: fadeIn 1s ease-in-out;
        }

        .container i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #f1c40f;
            animation: pulse 2s infinite;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        #countdown {
            font-size: 40px;
            font-weight: bold;
            color: #00ffcc;
            letter-spacing: 3px;
        }

        p {
            margin-top: 25px;
            font-size: 18px;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-tools"></i>
        <h1>Maaf, aplikasi sedang dalam proses update...!!</h1>
        <div id="countdown">02:00:00</div>
        <p>Terima Kasih atas kesabaran Anda.</p>
    </div>

    <script>
        // Durasi countdown dalam detik (2 jam)
        const countdownDuration = 2 * 60 * 60;

        // Cek apakah sudah ada waktu akhir tersimpan di localStorage
        let endTime = localStorage.getItem("updateEndTime");

        if (!endTime) {
            // Jika belum ada, set waktu akhir baru
            endTime = Date.now() + countdownDuration * 1000;
            localStorage.setItem("updateEndTime", endTime);
        } else {
            // Jika sudah ada, ubah string jadi integer
            endTime = parseInt(endTime);
        }

        function updateCountdown() {
            const now = Date.now();
            let remaining = Math.floor((endTime - now) / 1000);

            if (remaining < 0) remaining = 0;

            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;

            document.getElementById("countdown").textContent =
                String(hours).padStart(2, '0') + ":" +
                String(minutes).padStart(2, '0') + ":" +
                String(seconds).padStart(2, '0');

            if (remaining > 0) {
                setTimeout(updateCountdown, 1000);
            } else {
                document.getElementById("countdown").textContent = "Update Selesai!";
                localStorage.removeItem("updateEndTime");
            }
        }

        updateCountdown();
    </script>
</body>
</html>
