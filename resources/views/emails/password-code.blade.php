<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Password Reset | {{ config('app.name', 'SpendWise') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        border="0" style="background-color:#f1f5f9; width:100%;">
        <tr>
            <td align="center" style="padding:36px 14px;">

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    border="0" style="max-width:560px; background-color:#ffffff;
                    border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">

                    {{-- Brand --}}
                    <tr>
                        <td style="padding:28px 28px 22px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle" style="padding-right:12px;">
                                        <img
                                            src="{{ asset('images/spendwise-logo.png') }}"
                                            alt="SpendWise"
                                            width="44"
                                            height="44"
                                            style="display:block; width:44px; height:44px;
                                            object-fit:contain; border:0;"
                                        >
                                    </td>
                                    <td valign="middle">
                                        <div style="font-size:19px; line-height:26px;
                                            font-weight:700; color:#172554;">
                                            {{ config('app.name', 'SpendWise') }}
                                        </div>
                                        <div style="font-size:10px; line-height:16px;
                                            font-weight:600; letter-spacing:1.5px;
                                            color:#64748b;">
                                            SPEND SMART. LIVE WISE.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 28px;">
                            <div style="height:1px; background-color:#e2e8f0;"></div>
                        </td>
                    </tr>

                    {{-- Main content --}}
                    <tr>
                        <td style="padding:28px 28px 30px;">

                            <div style="width:46px; height:46px; line-height:46px;
                                text-align:center; border-radius:12px;
                                background-color:#eef2ff; color:#4338ca;
                                font-size:23px; font-weight:700;">
                                &#128274;
                            </div>

                            <h1 style="margin:22px 0 12px; font-size:27px;
                                line-height:35px; letter-spacing:-0.6px;
                                font-weight:700; color:#0f172a;">
                                Reset your password
                            </h1>

                            <p style="margin:0 0 24px; font-size:15px;
                                line-height:25px; color:#475569;">
                                We received a request to reset your SpendWise
                                password. Use the verification code below to
                                securely regain access to your account.
                            </p>

                            {{-- Verification code --}}
                            <table role="presentation" width="100%" cellspacing="0"
                                cellpadding="0" border="0"
                                style="background-color:#f8fafc;
                                border:1px solid #e2e8f0; border-radius:12px;">
                                <tr>
                                    <td align="center" style="padding:22px 12px;">
                                        <p style="margin:0 0 12px; font-size:11px;
                                            line-height:16px; font-weight:700;
                                            letter-spacing:1.8px; color:#64748b;">
                                            YOUR VERIFICATION CODE
                                        </p>

                                        <div style="font-family:Arial, Helvetica, sans-serif;
                                            font-size:32px; line-height:40px;
                                            font-weight:700; letter-spacing:5px;
                                            color:#4338ca; overflow-wrap:anywhere;">
                                            {{ $code }}
                                        </div>

                                        <p style="margin:12px 0 0; font-size:12px;
                                            line-height:18px; color:#64748b;">
                                            Enter this code on the password reset page.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Expiry --}}
                            <table role="presentation" cellspacing="0" cellpadding="0"
                                border="0" style="margin-top:20px;">
                                <tr>
                                    <td valign="middle" style="padding-right:8px;
                                        font-size:16px; color:#64748b;">
                                        &#9201;
                                    </td>
                                    <td valign="middle" style="font-size:14px;
                                        line-height:22px; color:#475569;">
                                        This code expires in
                                        <strong style="color:#0f172a;">
                                            {{ $minutes }} minutes
                                        </strong>.
                                    </td>
                                </tr>
                            </table>

                            {{-- Security notice --}}
                            <table role="presentation" width="100%" cellspacing="0"
                                cellpadding="0" border="0"
                                style="margin-top:24px; background-color:#fffbeb;
                                border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <p style="margin:0 0 5px; font-size:13px;
                                            line-height:19px; font-weight:700;
                                            color:#92400e;">
                                            Keep your account secure
                                        </p>
                                        <p style="margin:0; font-size:13px;
                                            line-height:21px; color:#92400e;">
                                            If you didn't request a password reset,
                                            you can safely ignore this email.
                                            Never share this code with anyone.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:22px 24px;
                            background-color:#f8fafc;
                            border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 6px; font-size:12px;
                                line-height:19px; font-weight:600;
                                color:#475569;">
                                {{ config('app.name', 'SpendWise') }}
                                &mdash; Spend Smart. Live Wise.
                            </p>
                            <p style="margin:0; font-size:11px;
                                line-height:18px; color:#94a3b8;">
                                Automated account security notification.
                            </p>
                        </td>
                    </tr>

                </table>

                <p style="max-width:480px; margin:18px auto 0;
                    font-size:11px; line-height:18px; text-align:center;
                    color:#94a3b8;">
                    Please do not reply with your verification code or password.
                </p>

            </td>
        </tr>
    </table>

</body>
</html>