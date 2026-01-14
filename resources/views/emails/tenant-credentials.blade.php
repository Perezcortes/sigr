<!DOCTYPE html>
<html>
<head>
    <title>Bienvenido</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color: #161848; text-align: center;">¡Bienvenido a Rentas!</h2>
        
        <p>Hola <strong>{{ $user->name }}</strong>,</p>
        
        <p>Se ha creado tu cuenta de acceso exitosamente. A continuación te compartimos tus credenciales para ingresar al portal:</p>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #161848; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Usuario/Correo:</strong> {{ $user->email }}</p>
            <p style="margin: 5px 0;"><strong>Contraseña:</strong> {{ $plainPassword }}</p>
        </div>

        <p style="text-align: center;">
            <a href="{{ url('/admin/login') }}" style="background-color: #161848; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Iniciar Sesión
            </a>
        </p>

        <p style="font-size: 12px; color: #777; margin-top: 30px; text-align: center;">
            Por seguridad, te recomendamos cambiar tu contraseña al ingresar.<br>
            Si no reconoces esta acción, ignora este correo.
        </p>
    </div>
</body>
</html>