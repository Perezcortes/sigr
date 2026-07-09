<!DOCTYPE html>
<html>
<head>
    <title>¿Vas a eliminar tu cuenta?</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="padding: 30px;">
            <p style="text-align: center; font-size: 20px; font-weight: bold; margin: 0 0 24px;">
                <span style="color: #161848;">Rentas</span><span style="color: #26CAD3;">.com</span>
            </p>

            <h2 style="color: #161848; text-align: center; margin: 0 0 16px;">Eliminar mi cuenta</h2>

            <p style="color: #333; line-height: 1.6; margin: 0 0 20px; text-align: center;">
                Gracias por haber usado <strong>rentas.com,</strong> esperamos que hayas encontrado tu inmueble ideal.
                Para eliminar tu cuenta permanentemente ingresa este código en el recuadro correspondiente en nuestro portal.
            </p>

            <p style="text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #161848; margin: 24px 0;">
                {{ $code }}
            </p>

            <p style="color: #777; font-size: 13px; text-align: center; margin: 0;">
                Si no fuiste tú o te equivocaste, ignora este e-mail.
            </p>
        </div>
        <div style="background-color: #161848; color: #ffffff; padding: 14px 30px; font-size: 11px;">
            Rentas.com
        </div>
    </div>
</body>
</html>
