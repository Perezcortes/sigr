<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Documentacion de APIs de Admrentas',
    title: 'Admrentas API'
)]
#[OA\Server(
    url: '{scheme}://{host}',
    description: 'Servidor actual por entorno',
    variables: [
        new OA\ServerVariable(
            serverVariable: 'scheme',
            default: 'https',
            enum: ['http', 'https']
        ),
        new OA\ServerVariable(
            serverVariable: 'host',
            default: 'localhost'
        ),
    ]
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token'
)]
class OpenApiSpec
{
}
