<table>
    <thead>
    <tr>
        <td colspan="7" align="right" valign="center" style="padding-right: 50px;">
            <img src="{{ public_path('images/logo-rentas-W.png') }}" height="60" style="margin-top: 15px;" />
        </td>
    </tr>
    
    <tr>
        <td colspan="7" align="center" style="font-size: 16px; font-weight: bold; color: #161848; height: 30px;">
            REPORTE COMPLETO DE INTERESADOS
        </td>
    </tr>

    <tr></tr>

    <tr style="background-color: #161848; color: #ffffff;">
        <th align="center" style="border: 1px solid #000000; height: 35px; vertical-align: middle;">NOMBRE</th>
        <th align="center" style="border: 1px solid #000000; vertical-align: middle;">TELÉFONO</th>
        <th align="center" style="border: 1px solid #000000; vertical-align: middle;">CORREO</th>
        <th align="center" style="border: 1px solid #000000; vertical-align: middle;">ETAPA</th>
        <th align="center" style="border: 1px solid #000000; vertical-align: middle;">ORIGEN</th>
        <th align="center" style="border: 1px solid #000000; vertical-align: middle;">MENSAJE</th>
        <th align="center" style="border: 1px solid #000000; vertical-align: middle;">FECHA REGISTRO</th>
    </tr>
    </thead>
    <tbody>
    @foreach($leads as $lead)
        @php
            // Lógica de colores para las etapas (Estilo Badge)
            $bgEtapa = '#FFFFFF'; // Blanco por defecto
            $colorTexto = '#000000';
            
            switch($lead->etapa) {
                case 'no_contactado':
                    $bgEtapa = '#FEE2E2'; // Rojo suave
                    $colorTexto = '#991B1B'; // Rojo oscuro
                    break;
                case 'ganado':
                    $bgEtapa = '#DCFCE7'; // Verde suave
                    $colorTexto = '#166534'; // Verde oscuro
                    break;
                case 'contactado':
                    $bgEtapa = '#FEF3C7'; // Amarillo suave
                    $colorTexto = '#92400E'; // Naranja oscuro
                    break;
                case 'perdido':
                case 'no_califica':
                    $bgEtapa = '#F3F4F6'; // Gris
                    $colorTexto = '#6B7280';
                    break;
            }
        @endphp

        <tr>
            <td style="border: 1px solid #dedede; font-weight: bold; color: #333;">{{ $lead->nombre }}</td>
            
            <td style="border: 1px solid #dedede;">{{ $lead->telefono }}</td>
            <td style="border: 1px solid #dedede; color: #2563EB; text-decoration: underline;">{{ $lead->correo }}</td>
            
            <td style="border: 1px solid #dedede; background-color: {{ $bgEtapa }}; color: {{ $colorTexto }}; font-weight: bold;">
                {{ ucfirst(str_replace('_', ' ', $lead->etapa)) }}
            </td>
            
            <td style="border: 1px solid #dedede;">{{ $lead->origen }}</td>
            <td style="border: 1px solid #dedede; font-size: 10px; color: #555;">{{ $lead->mensaje }}</td>
            <td style="border: 1px solid #dedede;">{{ $lead->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>