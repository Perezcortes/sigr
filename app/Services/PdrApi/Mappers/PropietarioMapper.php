<?php

namespace App\Services\PdrApi\Mappers;

use App\Services\PdrApi\Mappers\DocumentoMapper;

class PropietarioMapper
{
    /**
     * Punto de entrada principal para mapear cualquier propietario (Física o Moral)
     */
    public static function mapear($ownerRequest, $rentRecord): array
    {
        // Convertimos a minúsculas para evitar problemas con acentos o mayúsculas
        $tipoPersona = strtolower($ownerRequest->tipo_persona);

        if (str_contains($tipoPersona, 'fisica') || str_contains($tipoPersona, 'física')) {
            return self::mapearPersonaFisica($ownerRequest, $rentRecord);
        }

        return self::mapearPersonaMoral($ownerRequest, $rentRecord);
    }

    /**
     * MAPEADORES: PERSONA FÍSICA
     */
    private static function mapearPersonaFisica($req, $rent): array
    {
        return [
            'tipo_persona'    => 'Persona física',
            'datosPersonales' => self::getDatosPersonalesFisica($req, $rent),
            'datosInmueble'   => self::getDatosInmuebleFisica($req),
            'datosTercero'    => self::getDatosTerceroFisica($req), 
            'datosDocumentos' => DocumentoMapper::mapear($rent->ownerDocuments, 'Propietario', 'PF'),
        ];
    }

    private static function getDatosPersonalesFisica($req, $rent): array
    {
        $sustituyeDomicilio = strtolower($req->mismo_domicilio_fiscal) === 'si' ? 1 : 0;

        $datos = [
            'demail'               => $req->email,
            'mismoDomicilioFiscal' => $sustituyeDomicilio,
            'edoCivil'             => ucfirst(strtolower($req->estado_civil)),
            'nacionalidad'         => ucfirst(strtolower($req->nacionalidad)),
            
            'curp'                 => $req->curp,
            'dident'               => $req->tipo_identificacion, 
            'dcp'                  => $req->codigo_postal,
            'destado'              => $req->estado,
            'formaPago'            => $req->forma_pago,
            'regimen'              => $req->estado_civil === 'Casado' ? $req->regimen_conyugal : null,
            
            'calleFiscal'          => $req->calle_fiscal,
            'numExtFiscal'         => $req->numero_exterior_fiscal,
            'numIntFiscal'         => $req->numero_interior_fiscal,
            'cpFiscal'             => $req->codigo_postal_fiscal,
            'coloniaFiscal'        => $req->colonia_fiscal,
            'munFiscal'            => $req->municipio_fiscal,
            'estadoFiscal'         => $req->estado_fiscal,

            'dnombres'             => $req->nombres,
            'dapellidoP'           => $req->primer_apellido,
            'dapellidoM'           => $req->segundo_apellido,
            'drfc'                 => $req->rfc,
            'fechaNac'             => $req->fecha_nacimiento ? \Carbon\Carbon::parse($req->fecha_nacimiento)->format('Y-m-d') : null,
            'dtelefono'            => $req->telefono,
            'regimenFiscalPf'      => $req->regimen_fiscal,
            
            'dcalle'               => $req->calle,
            'dnumExt'              => $req->numero_exterior,
            'dnumInt'              => $req->numero_interior,
            'dcolonia'             => $req->colonia,
            'dciudad'              => $req->delegacion_municipio,
            
            'sexoPropPF'           => ucfirst(strtolower($req->sexo)),
            'referencias'          => $req->referencias_ubicacion,
            
            'otroMetodo'           => $req->forma_pago === 'Otro' ? $req->forma_pago_otro : null,
            'titular'              => $req->forma_pago === 'Transferencia' ? $req->titular_cuenta : null,
            'noCuenta'             => $req->forma_pago === 'Transferencia' ? $req->numero_cuenta : null,
            'banco'                => $req->forma_pago === 'Transferencia' ? $req->nombre_banco : null,
            'clabe'                => $req->forma_pago === 'Transferencia' ? $req->clabe_interbancaria : null,
        ];

        if (strtolower($datos['dident']) === 'pasaporte') {
            $datos['dident'] = 'PASSPORT';
        }

        if ($datos['nacionalidad'] === 'Extranjera') {
            $datos['especifiqueN'] = $req->nacionalidad_especifica;
        }

        if ($rent->tipo_poliza === 'PÓLIZA CON SEGURO') {
            if ($datos['nacionalidad'] === 'Extranjera') {
                $datos['paispf'] = $req->pais_origen;
                $datos['fechaVencimientoTarjeta'] = $req->fecha_vencimiento_tarjeta ? \Carbon\Carbon::parse($req->fecha_vencimiento_tarjeta)->format('Y-m-d') : null;
                $datos['nue'] = $req->nue;
                $datos['tipoResidencia'] = ucfirst(strtolower($req->tipo_residencia)); 
            }
        }

        return $datos;
    }

    private static function getDatosInmuebleFisica($req): array
    {
        $iva = $req->iva_renta;
        if ($iva === 'Mas IVA') {
            $iva = 'Más IVA';
        } elseif ($iva === 'Sin IVA') {
            $iva = 'SIN IVA';
        }

        $booleanToText = fn($val) => strtolower($val) === 'si' ? 'Sí' : 'No';

        return [
            'tieneMantenimiento'   => $booleanToText($req->paga_mantenimiento),
            'tipoInmueble'         => $req->tipo_inmueble,
            'usoSuelo'             => $req->uso_suelo,
            'mascotas'             => $booleanToText($req->mascotas),
            'tipo'                 => $req->mascotas_especifica, 
            
            'renta'                => $req->precio_renta,
            'iva'                  => $iva,
            'frecPago'             => $req->frecuencia_pago,
            'frecPagoOtra'         => $req->frecuencia_pago === 'Otra' ? $req->frecuencia_pago_otra : null,
            'condicionPago'        => $req->condiciones_pago,
            'instruccionesPago'    => $req->instrucciones_pago,
            'depositoGarantia'     => $req->deposito_garantia,

            'usuarioPagaMant'      => $req->quien_paga_mantenimiento,
            'cuotaIncluida'        => $req->mantenimiento_incluido_renta ? $booleanToText($req->mantenimiento_incluido_renta) : null,
            'precioMantenimiento'  => $req->costo_mantenimiento_mensual,

            'tieneSeguro'          => $booleanToText($req->requiere_seguro),
            'cobertura'            => $req->cobertura_seguro,
            'precioSeguro'         => $req->monto_cobertura_seguro,
            'servicios'            => $req->servicios_pagar,
            'inventario'           => $req->inmueble_inventario,
            
            'calle'                => $req->inmueble_calle,
            'numExt'               => $req->inmueble_numero_exterior,
            'numInt'               => $req->inmueble_numero_interior,
            'cp'                   => $req->inmueble_codigo_postal,
            'colonia'              => $req->inmueble_colonia,
            'ciudad'               => $req->inmueble_delegacion_municipio,
            'Estado'               => $req->inmueble_estado, 
            'referenciasUbicacion' => $req->inmueble_referencias,
        ];
    }

    private static function getDatosTerceroFisica($req): array
    {
        $seraRepresentado = $req->sera_representado === 'Si' ? 'Si' : 'No';

        $tipoIdentificacion = $req->representante_tipo_identificacion;
        if (strtolower($tipoIdentificacion) === 'pasaporte') {
            $tipoIdentificacion = 'PASSPORT';
        }

        $tipoRepresentacion = null;
        if ($seraRepresentado === 'Si') {
            $tipoRepresentacion = match ($req->tipo_representacion) {
                'Autorizacion para rentar'            => 'Autorización para rentar',
                'Mandato simple (carta poder)'        => 'Mandato simple (carta poder)',
                'Carta poder ratificada ante notario' => 'Carta Poder ratificada ante notario',
                'Poder notarial'                      => 'Poder notarial',
                default                               => $req->tipo_representacion,
            };
        }

        return [
            'terceroPermitido' => $seraRepresentado,
            'temail'           => $seraRepresentado === 'Si' ? $req->representante_email : $req->email,
            'tnombres'         => $seraRepresentado === 'Si' ? $req->representante_nombres : null,
            'tapellidoP'       => $seraRepresentado === 'Si' ? $req->representante_primer_apellido : null,
            'tapellidoM'       => $seraRepresentado === 'Si' ? $req->representante_segundo_apellido : null,
            'trfc'             => $seraRepresentado === 'Si' ? $req->representante_rfc : null,
            'tcurp'            => $seraRepresentado === 'Si' ? $req->representante_curp : null,
            'sexoTercero'      => $seraRepresentado === 'Si' ? ucfirst(strtolower($req->representante_sexo)) : null,
            'ttelefono'        => $seraRepresentado === 'Si' ? $req->representante_telefono : null,
            
            'tcalle'           => $seraRepresentado === 'Si' ? $req->representante_calle : null,
            'tnumExt'          => $seraRepresentado === 'Si' ? $req->representante_numero_exterior : null,
            'tnumInt'          => $seraRepresentado === 'Si' ? $req->representante_numero_interior : null,
            'tcolonia'         => $seraRepresentado === 'Si' ? $req->representante_colonia : null,
            'tciudad'          => $seraRepresentado === 'Si' ? $req->representante_delegacion_municipio : null,
            'testado'          => $seraRepresentado === 'Si' ? $req->representante_estado : null,
            'tcp'              => $seraRepresentado === 'Si' ? $req->representante_codigo_postal : null,
            'tcomentarios'     => $seraRepresentado === 'Si' ? $req->representante_referencias : null,
            
            'tipoRepreTercero' => $tipoRepresentacion,
            'terceroTipoident' => $tipoIdentificacion,
        ];
    }

    /**
     * MAPEADORES: PERSONA MORAL
     */
    private static function mapearPersonaMoral($req, $rent): array
    {
        return [
            'tipo_persona'    => 'Persona moral',
            'datosEmpresa'    => self::getDatosEmpresaMoral($req),
            'datosInmueble'   => self::getDatosInmuebleFisica($req), 
            'datosTercero'    => self::getDatosTerceroFisica($req), // Lo agregamos porque Jona lo espera
            'datosDocumentos' => DocumentoMapper::mapear($rent->ownerDocuments, 'Propietario', 'PM'),
        ];
    }

    private static function getDatosEmpresaMoral($req): array
    {
        // Booleanos adaptados a las reglas de Jona
        $sustituyeDomicilio = strtolower($req->mismo_domicilio_fiscal) === 'si' ? 1 : 0;
        $facultadesEnActa   = strtolower($req->facultades_en_acta) === 'si' ? 'Sí' : 'No';

        return [
            // Obligatorios Base
            'correo_electronico_moral' => $req->email,
            'fechaConstNot_moral'      => $req->fecha_constitucion ? \Carbon\Carbon::parse($req->fecha_constitucion)->format('Y-m-d') : null,
            'r_correo_electronico'     => $req->apoderado_email,
            'mismoDomicilioFiscal'     => $sustituyeDomicilio,
            'r_facultadEmp'            => $facultadesEnActa,

            // Domicilio Fiscal
            'calleFiscal'              => $req->calle_fiscal,
            'numExtFiscal'             => $req->numero_exterior_fiscal,
            'numIntFiscal'             => $req->numero_interior_fiscal,
            'cpFiscal'                 => $req->codigo_postal_fiscal,
            'coloniaFiscal'            => $req->colonia_fiscal,
            'munFiscal'                => $req->municipio_fiscal,
            'estadoFiscal'             => $req->estado_fiscal,

            // Datos Generales de la Empresa
            'nombre_empresa'           => $req->razon_social,
            'rfc_moral'                => $req->rfc,
            'regimenFiscalPm'          => $req->regimen_fiscal,
            'telefono_moral'           => $req->telefono,

            // Domicilio Particular de la Empresa
            'calleP_moral'             => $req->calle,
            'numExtP_moral'            => $req->numero_exterior,
            'numIntP_moral'            => $req->numero_interior,
            'cpP_moral'                => $req->codigo_postal,
            'coloniaP_moral'           => $req->colonia,
            'municipioP_moral'         => $req->delegacion_municipio,
            'estadoP_moral'            => $req->estado,
            'referenciasP_moral'       => $req->referencias_ubicacion,

            // Forma de Pago
            'formaPago_moral'          => $req->forma_pago,
            'otroMetodo_moral'         => $req->forma_pago === 'Otro' ? $req->forma_pago_otro : null,
            'titular_moral'            => $req->forma_pago === 'Transferencia' ? $req->titular_cuenta : null,
            'noCuenta_moral'           => $req->forma_pago === 'Transferencia' ? $req->numero_cuenta : null,
            'banco_moral'              => $req->forma_pago === 'Transferencia' ? $req->nombre_banco : null,
            'clabe_moral'              => $req->forma_pago === 'Transferencia' ? $req->clabe_interbancaria : null,

            // Acta Constitutiva
            'nombreNot_moral'          => $req->notario_nombres,
            'apellidoPNot_moral'       => $req->notario_primer_apellido,
            'apellidoMNot_moral'       => $req->notario_segundo_apellido,
            'numEscritNot_moral'       => $req->numero_escritura,
            'numNotario_moral'         => $req->notario_numero,
            'estadoRegistro'           => $req->estado_registro,
            'ciudadRegistro'           => $req->ciudad_registro,
            'numRegistroEmp'           => $req->numero_registro_inscripcion,
            'giroComerNot_moral'       => $req->giro_comercial,

            // Representante / Apoderado Legal
            'r_nombre'                 => $req->apoderado_nombres,
            'r_apellidoP'              => $req->apoderado_primer_apellido,
            'r_apellidoM'              => $req->apoderado_segundo_apellido,
            'r_curp'                   => $req->apoderado_curp,
            'sexoRepLegal'             => ucfirst(strtolower($req->apoderado_sexo)),
            'r_telefono'               => $req->apoderado_telefono,
            'r_calle'                  => $req->apoderado_calle,
            'r_numExt'                 => $req->apoderado_numero_exterior,
            'r_numInt'                 => $req->apoderado_numero_interior,
            'r_cp'                     => $req->apoderado_cp,
            'r_colonia'                => $req->apoderado_colonia,
            'r_municipio'              => $req->apoderado_municipio,
            'r_estado'                 => $req->apoderado_estado,

            // Facultades (Solo obligatorias si "No" constan en el acta principal)
            'r_numEscActaLeg'          => $facultadesEnActa === 'No' ? $req->escritura_publica_numero : null,
            'r_numNotarioFacuEmp'      => $facultadesEnActa === 'No' ? $req->notario_numero_facultades : null,
            'r_fechaEscLeg'            => $facultadesEnActa === 'No' && $req->fecha_escritura_facultades ? \Carbon\Carbon::parse($req->fecha_escritura_facultades)->format('Y-m-d') : null,
            'r_numInscripcion'         => $facultadesEnActa === 'No' ? $req->numero_inscripcion_registro_publico : null,
            'r_estadoRegistro'         => $facultadesEnActa === 'No' ? $req->estado_registro_facultades : null,
            'r_ciudadRegistro'         => $facultadesEnActa === 'No' ? $req->ciudad_registro_facultades : null,
            'r_tipoPrestacionLeg'      => $facultadesEnActa === 'No' ? $req->tipo_representacion_moral : null,
            'r_otroTipoPres'           => $facultadesEnActa === 'No' && $req->tipo_representacion_moral === 'Otro' ? $req->tipo_representacion_otro : null,
        ];
    }

    /**
     * Mapea la sección "Datos del Inmueble" exclusivo para Persona Moral
     */
    private static function getDatosInmuebleMoral($req): array
    {
        // IVA para las reglas de Jona
        $iva = $req->iva_renta;
        if ($iva === 'Mas IVA') {
            $iva = 'Más IVA';
        } elseif ($iva === 'Sin IVA') {
            $iva = 'SIN IVA';
        }

        // Traducción de booleanos (de "si/no" a "Sí/No")
        $booleanToText = fn($val) => strtolower($val) === 'si' ? 'Sí' : 'No';

        return [
            // Obligatorios
            'tieneMantenimiento_moral'  => $booleanToText($req->paga_mantenimiento),

            // Inmueble y uso (Con sufijo _moral)
            'tipoInmueble_moral'        => $req->tipo_inmueble,
            'usoSuelo_moral'            => $req->uso_suelo,
            'mascotas_moral'            => $booleanToText($req->mascotas),
            'tipo_moral'                => $req->mascotas === 'si' ? $req->mascotas_especifica : null, 
            
            // Financieros (Con sufijo _moral)
            'iva_moral'                 => $iva,
            'frecPago_moral'            => $req->frecuencia_pago,
            'frecPagoOtra_moral'        => $req->frecuencia_pago === 'Otra' ? $req->frecuencia_pago_otra : null,
            'condicionPago_moral'       => $req->condiciones_pago,
            'instruccionesPago_moral'   => $req->instrucciones_pago,
            'depositoGarantia_moral'    => $req->deposito_garantia,

            // Mantenimiento (Con sufijo _moral)
            'usuarioPagaMant_moral'     => $req->paga_mantenimiento === 'si' ? $req->quien_paga_mantenimiento : null,
            'cuotaIncluida_moral'       => $req->paga_mantenimiento === 'si' && $req->mantenimiento_incluido_renta ? $booleanToText($req->mantenimiento_incluido_renta) : null,
            'precioMantenimiento_moral' => $req->paga_mantenimiento === 'si' ? $req->costo_mantenimiento_mensual : null,

            // Seguro y Extras (Con sufijo _moral)
            'tieneSeguro_moral'         => $booleanToText($req->requiere_seguro),
            'cobertura_moral'           => $req->requiere_seguro === 'si' ? $req->cobertura_seguro : null,
            'precioSeguro_moral'        => $req->requiere_seguro === 'si' ? $req->monto_cobertura_seguro : null,
            'servicios_moral'           => $req->servicios_pagar,
            'inventario_moral'          => $req->inmueble_inventario,
            
            'renta'                     => $req->precio_renta,
            'calle'                     => $req->inmueble_calle,
            'numExt'                    => $req->inmueble_numero_exterior,
            'numInt'                    => $req->inmueble_numero_interior,
            'cp'                        => $req->inmueble_codigo_postal,
            'colonia'                   => $req->inmueble_colonia,
            'ciudad'                    => $req->inmueble_delegacion_municipio,
            'Estado'                    => $req->inmueble_estado, 
            'referenciasUbicacion'      => $req->inmueble_referencias,
        ];
    }
}