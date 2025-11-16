<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f4f4f4; padding: 20px; border-radius: 5px;">
        <h2 style="color: #333; margin-top: 0;">Welcome, {{ $user->name }}!</h2>
        
        <p>Your employee account has been created successfully. To get started, please set your password by clicking the button below:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}" 
               style="background-color: #007bff; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                Set Your Password
            </a>
        </div>
        
        <p style="color: #666; font-size: 14px;">Or copy and paste this link into your browser:</p>
        <p style="color: #666; font-size: 12px; word-break: break-all;">{{ $resetUrl }}</p>
        
        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            <strong>Note:</strong> This link will expire in 60 minutes for security reasons.
        </p>
        
        <p style="color: #666; font-size: 14px; margin-top: 20px;">
            If you did not expect this email, please ignore it.
        </p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        
        <p style="color: #999; font-size: 12px; margin: 0;">
            This is an automated message, please do not reply to this email.
        </p>
    </div>
</body>
</html>

