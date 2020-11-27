<html>
    <head>
        <style type="text/css">
            /* CLIENT-SPECIFIC STYLES */
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; }

            /* RESET STYLES */
            img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
            table { border-collapse: collapse !important; }
            body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }

            /* iOS BLUE LINKS */
            a[x-apple-data-detectors] {
                color: inherit !important;
                text-decoration: none !important;
                font-size: inherit !important;
                font-family: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
            }

            /* MOBILE STYLES */
            @media screen and (max-width:600px){
                h1 {
                    font-size: 32px !important;
                    line-height: 32px !important;
                }
            }

            /* ANDROID CENTER FIX */
            div[style*="margin: 16px 0;"] { margin: 0 !important; }
        </style>

        <style type="text/css">

        </style>
    </head>
    <body style="background-color: #f4f4f4; margin: 0 !important; padding: 0 !important;">

        <!-- HIDDEN PREHEADER TEXT -->
        <div style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; font-family: Helvetica, Arial, sans-serif; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
            {{ $otp_title }}
        </div>

        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <!-- LOGO -->
            <tr>
                <td bgcolor="#f4f4f4" align="center">
                    <!--[if (gte mso 9)|(IE)]>
                    <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                    <tr>
                    <td align="center" valign="top" width="600">
                    <![endif]-->
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;" >
                        <tr>
                            <td align="center" valign="top" style="padding: 40px 10px 40px 10px;">
                                <a href="https://haitutor.id" target="_blank">
                                    <img alt="Logo" src="http://haitutor.id/backend-educhat/temp/haitutor-wtext.png" width="50" height="50" style="display: block; width: 169px; max-width: 100; min-width: 10; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-size: 18px;" border="0">
                                </a>
                            </td>
                        </tr>
                    </table>
                    <!--[if (gte mso 9)|(IE)]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            <!-- HERO -->
            <tr>
                <td bgcolor="#f4f4f4" align="center" style="padding: 0px 10px 0px 10px;">
                    <!--[if (gte mso 9)|(IE)]>
                    <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                    <tr>
                    <td align="center" valign="top" width="600">
                    <![endif]-->
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;" >
                        <tr>
                            <td bgcolor="#ffffff" align="center" valign="top" style="padding: 30px 20px 20px 20px; border-radius: 4px 4px 0px 0px; color: #111111; font-family: Helvetica, Arial, sans-serif; font-size: 36px; font-weight: 400; letter-spacing: 4px; line-height: 48px;">
                              <h1 style="font-size: 25px; font-weight: 400; margin: 0; letter-spacing: 0px;">{{ $otp_title }}</h1>
                            </td>
                        </tr>
                    </table>
                    <!--[if (gte mso 9)|(IE)]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            <!-- COPY BLOCK -->
            <tr>
                <td bgcolor="#f4f4f4" align="center" style="padding: 0px 10px 0px 10px;">
                    <!--[if (gte mso 9)|(IE)]>
                    <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                    <tr>
                    <td align="center" valign="top" width="600">
                    <![endif]-->
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;" >
                      <!-- COPY -->
                      <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 20px 30px 0px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 400; line-height: 25px;" >
                          <p style="margin: 0;"> {{ $otp_message }}</p>
                        </td>
                      </tr>
                      <!-- BULLETPROOF BUTTON -->
                      <tr>
                        <td bgcolor="#ffffff" align="left">
                          <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                              <td bgcolor="#ffffff" align="center" style="padding: 15px 30px 15px 30px;">
                                <table border="0" cellspacing="0" cellpadding="0">
                                  <tr>
                                      <td align="center" style="border-radius: 3px;" bgcolor="#ffffff"> <p style="font-size: 30px; font-family: Helvetica, Arial, sans-serif; color: #000000; text-decoration: none; color: #000000; text-decoration: none; padding: 0px 20px 0px 20px; border-radius: 2px; border: 1px solid #ffffff; display: inline-block;"> <b>{{ $otp }}</b>  </p> </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>

                            @if ($otp_type == OTPModel::OTP_TYPE["VERIFY_EMAIL"])

                            @else
                                <tr>
                                    <td bgcolor="#ffffff" align="center" style="padding: 0px 30px 60px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 400; line-height: 25px;" >
                                    <p style="margin: 0;"><b> Jangan berikan KODE OTP kepada siapapun </b></p>
                                    </td>
                                </tr>
                            @endif
                          </table>
                        </td>
                      </tr>
                      <!-- COPY -->
                      {{-- <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 0px 30px 0px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 400; line-height: 25px;" >
                          <p style="margin: 0;">If that doesn't work, copy and paste the following link in your browser:</p>
                        </td>
                      </tr> --}}
                      <!-- COPY -->
                        {{-- <tr>
                          <td bgcolor="#ffffff" align="left" style="padding: 20px 30px 20px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 400; line-height: 25px;" >
                            <p style="margin: 0;"><a href="http://litmus.com" target="_blank" style="color: #4A35EA;">XXX.XXXXXXX.XXX/XXXXXXXXXXXXX</a></p>
                          </td>
                        </tr> --}}
                      <!-- COPY -->
                      <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 0px 30px 20px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 400; line-height: 25px;" >
                          <p style="margin: 0;"> {{ $otp_action_user}}</p>
                        </td>
                      </tr>
                      <!-- COPY -->
                      <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 0px 30px 40px 30px; border-radius: 0px 0px 4px 4px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 400; line-height: 25px;" >
                          <p style="margin: 0;">Salam,<br>Tim HaiTutor.</p>
                        </td>
                      </tr>
                    </table>
                    <!--[if (gte mso 9)|(IE)]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            <!-- FOOTER -->
            <tr>
                <td bgcolor="#f4f4f4" align="center" style="padding: 0px 10px 0px 10px;">
                    <!--[if (gte mso 9)|(IE)]>
                    <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                    <tr>
                    <td align="center" valign="top" width="600">
                    <![endif]-->
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;" >
                      <!-- NAVIGATION -->
                      {{-- <tr>
                        <td bgcolor="#f4f4f4" align="left" style="padding: 30px 30px 30px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; line-height: 18px;" >
                          <p style="margin: 0;">
                            <a href="http://litmus.com" target="_blank" style="color: #111111; font-weight: 700;">Dashboard</a> -
                            <a href="http://litmus.com" target="_blank" style="color: #111111; font-weight: 700;">Billing</a> -
                            <a href="http://litmus.com" target="_blank" style="color: #111111; font-weight: 700;">Help</a>
                          </p>
                        </td>
                      </tr> --}}
                      <!-- PERMISSION REMINDER -->
                      <tr>
                        <td bgcolor="#f4f4f4" align="left" style="padding: 20px 30px 30px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; line-height: 18px;" >
                          <p style="margin: 0;">Anda menerima email ini karena terdaftar di <a target="_blank" href="https://haitutor.id">haitutor.id</a></p>
                        </td>
                      </tr>
                      <!-- ADDRESS -->
                      <tr>
                        <td bgcolor="#f4f4f4" align="left" style="padding: 0px 30px 5px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; line-height: 18px;" >
                          <p style="margin: 0;">HaiTutor</p>
                        </td>
                      </tr>
                      <tr>
                        <td bgcolor="#f4f4f4" align="left" style="padding: 0px 30px 10px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; line-height: 18px;" >
                          <p style="margin: 0;">Anak Riang, Ortupun Senang</p>
                        </td>
                      </tr>
                      <tr>
                      <tr>
                        <td bgcolor="#f4f4f4" align="left" style="padding: 0px 30px 10px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; line-height: 18px;" >
                        <p style="margin: 0;">{{ $no_telp }}</p>
                        </td>
                      </tr>
                      <tr>
                        <td bgcolor="#f4f4f4" align="left" style="padding: 0px 30px 30px 30px; color: #666666; font-family: Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; line-height: 18px;" >
                          <p style="margin: 0;"> {{ $alamat }}</p>
                        </td>
                      </tr>
                    </table>
                    <!--[if (gte mso 9)|(IE)]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
        </table>

        </body>
</html>
