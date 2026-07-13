<!DOCTYPE html>
<html>
<head>
    <title>Bienvenido</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="padding: 30px;">
            <p style="text-align: center; margin: 0 0 24px;">
                <img src="{{ asset('images/logo-rentas-w.png') }}" alt="Rentas.com" style="height: 28px;">
            </p>

            <div style="position: relative;">
                <div style="width: 60%;">
                    <h2 style="color: #161848; margin: 0 0 16px;">Hola, {{ $user->name }}</h2>

                    <p style="color: #333; line-height: 1.6; margin: 0 0 16px;">
                        Ya casi accedes a nuestra app exclusiva.
                    </p>

                    <p style="color: #333; line-height: 1.6; margin: 0 0 16px;">
                        Por favor descarga la aplicación e ingresa el usuario y contraseña para comenzar a configurar tu cuenta.
                    </p>

                    <p style="color: #333; line-height: 1.6; margin: 0;">
                        Por seguridad, te recomendamos cambiar tu contraseña al ingresar.
                    </p>
                </div>

                <img src="{{ asset('images/app-phone-mockup.png') }}" alt="Vista previa de la app" style="position: absolute; top: 190px; right: -20px; width: 120px;">
            </div>

            <div style="border: 1px solid #FF5631; border-radius: 6px; padding: 15px; margin: 24px 0 24px;">
                <p style="margin: 5px 0;"><strong>Usuario/correo:</strong> {{ $user->email }}</p>
                <p style="margin: 5px 0;"><strong>Contraseña:</strong> {{ $plainPassword }}</p>
            </div>

            <table role="presentation" align="center" style="margin: 0 auto 20px;">
                <tr>
                    <td style="padding: 0 6px;">
                        <a href="#">
                            <img src="{{ asset('images/badge-google-play.png') }}" alt="Disponible en Google Play" style="height: 44px;">
                        </a>
                    </td>
                    <td style="padding: 0 10px;">
                        <img src="{{ asset('images/app-icon-mini.png') }}" alt="Rentas.com" style="height: 70px;">
                    </td>
                    <td style="padding: 0 6px;">
                        <a href="#">
                            <img src="{{ asset('images/badge-app-store.png') }}" alt="Disponible en App Store" style="height: 44px;">
                        </a>
                    </td>
                </tr>
            </table>

            <p style="color: #333; line-height: 1.6; text-align: center; margin: 0 0 16px;">
                Da clic en la imagen perteneciente a tu tienda virtual para bajar la aplicación.
            </p>

            <p style="color: #777; font-size: 13px; text-align: center; margin: 0;">
                Si no reconoces esta acción, ignora este correo.
            </p>
        </div>
        <div style="background-color: #161848; color: #ffffff; padding: 14px 30px; font-size: 11px;">
            Rentas.com
        </div>
    </div>
</body>
</html>
