<html>
    <head>
        <style>
            #container{
                text-align: center;
            }
            #header{
                background-color: #2165D9;
                text-align: center;
                color: white;
                height: 50px;
                position: relative;
            }
            #header h2{
                margin: 0;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            #footer{
                background-color: #2165D9;
                text-align: center;
                color: white;
                height: 50px;
                position: relative;
            }
            #footer p{
                margin: 0;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
        </style>
    </head>
    <body>
        <div id="header">
            <h2>HaiTutor</h2>
        </div>
        <div class="container text-center" id="container">
            <img src="https://haitutor.id/restfull_api/temp/haitutor_splash.png" alt="">
            
            <br>
            <p>Kode OTP Anda adalah: </p>
            <h1 class="text-center">{{ $otp }}</h1>
        </div>
        <br>
        <div id="footer">
            <p>Copyright © 2020 Vokanesia. All Right Reserved</p>
        </div>
    </body>
</html>