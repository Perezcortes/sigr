<?php

namespace App\Services\PdrApi\Mappers;

use Carbon\Carbon;
use App\Services\PdrApi\Mappers\DocumentoMapper;

class InquilinoMapper
{
    /**
     * Punto de entrada principal para mapear al inquilino completo.
     */
    public static function mapear($tenantRequest, $rentRecord): array
    {
        if ($tenantRequest->tipo_persona === 'moral') {
            return self::mapearPersonaMoral($tenantRequest, $rentRecord);
        }

        return self::mapearPersonaFisica($tenantRequest, $rentRecord);
    }

    // PERSONA FÍSICA

    private static function mapearPersonaFisica($req, $rent): array
    {
        return [
            'tipo_persona'        => 'Persona física', 
            'datosPersonales'     => self::getDatosPersonalesFisica($req),
            'datosEmpleoIngresos' => self::getEmpleoEIngresosFisica($req, $rent),
            'datosUsoPropiedad'   => self::getUsoPropiedadFisica($req, $rent),
            'datosReferencias'    => self::getReferenciasFisica($req, $rent),
            'datosDocumentos'     => DocumentoMapper::mapear($rent->tenantDocuments, 'Inquilino', 'PF')
        ];
    }

    private static function getDatosPersonalesFisica($req): array
    {
        return [
            // DATOS OBLIGATORIOS Y BOOLEANOS
            'email'                => $req->email,
            'fechaNac'             => $req->fecha_nacimiento ? \Carbon\Carbon::parse($req->fecha_nacimiento)->format('Y-m-d') : null,
            // 1 = Sí, 0 = No
            'mismoDomicilioFiscal' => strtolower($req->mismo_domicilio_fiscal) === 'si' ? 1 : 0,
            // 1 = Masculino, 0 = Femenino
            'sexo'                 => strtolower($req->sexo) === 'masculino' ? 1 : 0,
            // 1 = Soltero, 0 = Casado
            'edoCivil'             => strtolower($req->estado_civil) === 'soltero' ? 1 : 0,
            // 1 = Extranjera, 0 = Mexicana
            'nacionalidad'         => strtolower($req->nacionalidad) === 'extranjera' ? 1 : 0,

            // INFORMACIÓN PERSONAL GENERAL
            'iden'                 => $req->tipo_identificacion,
            'nombres'              => $req->nombres,
            'apellidoP'            => $req->primer_apellido,
            'apellidoM'            => $req->segundo_apellido,
            'rfc'                  => $req->rfc,
            'curp'                 => $req->curp,
            'celular'              => $req->telefono_celular,
            'telefonoP'            => $req->telefono_fijo, // teléfono fijo
            'situacion'            => $req->situacion_habitacional,

            // EXTRANJEROS (Aplica para Póliza con Seguro)
            'especifiqueN'            => $req->nacionalidad_especifica,
            'paispf'                  => $req->pais_origen ? (int) $req->pais_origen : null,
            'fechaVencimientoTarjeta' => $req->fecha_vencimiento_tarjeta ? \Carbon\Carbon::parse($req->fecha_vencimiento_tarjeta)->format('Y-m-d') : null,
            'nue'                     => $req->nue,
            'tipoResidencia'          => $req->tipo_residencia ? ucfirst($req->tipo_residencia) : null,

            // DATOS DEL CÓNYUGE
            'nombrec'              => $req->conyuge_nombres,
            'apellidoPc'           => $req->conyuge_primer_apellido,
            'apellidoMc'           => $req->conyuge_segundo_apellido,
            'telefonoc'            => $req->conyuge_telefono,

            // DOMICILIO ACTUAL
            'calle'                 => $req->calle,
            'numExt'                => $req->numero_exterior,
            'numInt'                => $req->numero_interior,
            'cp'                    => $req->codigo_postal,
            'colonia'               => $req->colonia,
            'mun'                   => $req->delegacion_municipio,
            'estado'                => $req->estado,
            'metrosCuadradosActual' => $req->metros_cuadrados,

            // DOMICILIO FISCAL (Si es diferente)
            'calleFiscal'          => $req->calle_fiscal,
            'numExtFiscal'         => $req->numero_exterior_fiscal,
            'numIntFiscal'         => $req->numero_interior_fiscal,
            'cpFiscal'             => $req->codigo_postal_fiscal,
            'coloniaFiscal'        => $req->colonia_fiscal,
            'munFiscal'            => $req->municipio_fiscal,
            'estadoFiscal'         => $req->estado_fiscal,

            // ARRENDADOR ACTUAL
            'nombrea'              => $req->arrendador_actual_nombres,
            'apellidoPa'           => $req->arrendador_actual_primer_apellido,
            'apellidoMa'           => $req->arrendador_actual_segundo_apellido,
            'telefonoa'            => $req->arrendador_actual_telefono,
            'renta'                => $req->renta_actual ? (float) $req->renta_actual : null,
            'anioRenta'            => $req->ocupa_desde_ano ? (int) $req->ocupa_desde_ano : null,
        ];
    }

    private static function getEmpleoEIngresosFisica($req, $rent): array
    {
        return [
            // DATOS DE EMPLEO BASE
            'regimenFiscalPf' => $req->regimen_fiscal ?? 'Asalariado',
            // Jona pide fecha obligatoria en formato Y-m-d
            'fechaIng'        => $rent->fecha_ingreso ? \Carbon\Carbon::parse($rent->fecha_ingreso)->format('Y-m-d') : null,
            'tipoEmp'         => $rent->tipo_empleo ?? 'Empleado',
            'profesion'       => $rent->profesion_oficio_puesto,

            // DOMICILIO DE EMPLEO / CONTACTO
            'tel'             => $rent->telefono_empleo,
            'ext'             => $rent->extension_empleo,
            'empresa'         => $rent->empresa_trabaja,
            'calle'           => $rent->calle_empleo, 
            'numExt'          => $rent->numero_exterior_empleo,
            'numInt'          => $rent->numero_interior_empleo,
            'colonia'         => $rent->colonia_empleo,
            'mun'             => $rent->delegacion_municipio_empleo,
            'estado'          => $rent->estado_empleo,
            'cp'              => $rent->codigo_postal_empleo,

            // JEFE INMEDIATO
            'nombrej'         => $rent->jefe_nombres,
            'apellidoPj'      => $rent->jefe_primer_apellido,
            'apellidoMj'      => $rent->jefe_segundo_apellido,
            'telefonoj'       => $rent->jefe_telefono,
            'extj'            => $rent->jefe_extension,

            // INGRESOS
            'ingresos'        => $rent->ingreso_mensual_comprobable ? (float) $rent->ingreso_mensual_comprobable : 0,
            'ingresoFam'      => $rent->ingreso_mensual_no_comprobable ? (float) $rent->ingreso_mensual_no_comprobable : 0,
            'numPerDep'       => $req->numero_personas_dependen ? (int) $req->numero_personas_dependen : 0,

            // OTRA PERSONA QUE APORTA
            'numPerIngre'     => $rent->numero_personas_aportan ? (int) $rent->numero_personas_aportan : 0,
            'nombrea'         => $rent->persona_aporta_nombres,
            'apellidoPa'      => $rent->persona_aporta_primer_apellido,
            'apellidoMa'      => $rent->persona_aporta_segundo_apellido,
            'parentesco'      => $rent->persona_aporta_parentesco,
            'telefonoA'       => $rent->persona_aporta_telefono,
            'empresaA'        => $rent->persona_aporta_empresa,
            'ingresoA'        => $rent->persona_aporta_ingreso_comprobable ? (float) $rent->persona_aporta_ingreso_comprobable : 0,
        ];
    }

    private static function getUsoPropiedadFisica($req, $rent): array
    {
        $tipoInmueble = $rent->tipo_inmueble === 'residencial' ? 'Inmuebles Residenciales' : 'Inmuebles Comerciales';

        $datos = []; 

        // INMUEBLE RESIDENCIAL
        if ($rent->tipo_inmueble === 'residencial') {
            $datos = [
                'adultos'     => $req->numero_adultos ? (int) $req->numero_adultos : 0,
                'menores'     => $req->tiene_menores == 1 ? 0 : 1, 
                'mascotas'    => $req->tiene_mascotas == 1 ? 1 : 0, 
                'adulto1'     => $req->nombre_adulto_1,
                'adulto2'     => $req->nombre_adulto_2,
                'adulto3'     => $req->nombre_adulto_3,
                'adulto4'     => $req->nombre_adulto_4,
                'cuantos'     => $req->tiene_menores == 1 ? (int) $req->cuantos_menores : null,
                'motivo'      => $req->motivo_cambio_domicilio,
                'especifique' => $req->tiene_mascotas == 1 ? $req->especificar_mascotas : null,
            ];
        }

        // INMUEBLE COMERCIAL
        else {
            $sustituyeDomicilio = $req->sustituye_otro_domicilio == 1 ? 'Sí' : 'No';

            $datos = array_merge($datos, [
                'tipoInmueble'             => $tipoInmueble, // Solo lo mandamos si es comercial
                'uso_sustituirDomicilioPF' => $sustituyeDomicilio,
                
                // Opcionales Comercial
                'giroNegocio'              => $req->giro_negocio,
                'experienciaGiro'          => $req->experiencia_giro,
                'uso_propositoPF'          => $req->propositos_arrendamiento,
                
                // Estos campos SOLO se envían si NO sustituye otro domicilio (o se envían vacíos)
                // estos campos se llenan si sustituye_otro_domicilio == 1
                // Revisar esto con Jona si su validación dice: "si sustituye == 'Sí', exige uso_estadoPF"
                'uso_callePF'              => $req->domicilio_anterior_calle,
                'uso_numExtPF'             => $req->domicilio_anterior_numero_exterior,
                'uso_numIntPF'             => $req->domicilio_anterior_numero_interior,
                'uso_codigoPosPF'          => $req->domicilio_anterior_codigo_postal,
                'uso_coloniaPF'            => $req->domicilio_anterior_colonia,
                'uso_municipioPF'          => $req->domicilio_anterior_delegacion_municipio,
                'uso_estadoPF'             => $req->domicilio_anterior_estado,
                
                'uso_motivoPF'             => null, 
            ]);
        }

        return $datos;
    }

    private static function getReferenciasFisica($req, $rent): array
    {
        return [
            // REFERENCIA PERSONAL 1
            'nombrer1'    => $req->referencia_personal1_nombres,
            'apellidoPr1' => $req->referencia_personal1_primer_apellido,
            'apellidoMr1' => $req->referencia_personal1_segundo_apellido,
            'relacionr1'  => $req->referencia_personal1_relacion,
            'telefonor1'  => $req->referencia_personal1_telefono,

            // REFERENCIA PERSONAL 2
            'nombrer2'    => $req->referencia_personal2_nombres,
            'apellidoPr2' => $req->referencia_personal2_primer_apellido,
            'apellidoMr2' => $req->referencia_personal2_segundo_apellido,
            'relacionr2'  => $req->referencia_personal2_relacion,
            'telefonor2'  => $req->referencia_personal2_telefono,

            // REFERENCIA FAMILIAR 1
            'nombref1'    => $req->referencia_familiar1_nombres,
            'apellidoPf1' => $req->referencia_familiar1_primer_apellido,
            'apellidoMf1' => $req->referencia_familiar1_segundo_apellido,
            'relacionf1'  => $req->referencia_familiar1_relacion,
            'telefonof1'  => $req->referencia_familiar1_telefono,

            // REFERENCIA FAMILIAR 2
            'nombref2'    => $req->referencia_familiar2_nombres,
            'apellidoPf2' => $req->referencia_familiar2_primer_apellido,
            'apellidoMf2' => $req->referencia_familiar2_segundo_apellido,
            'relacionf2'  => $req->referencia_familiar2_relacion,
            'telefonof2'  => $req->referencia_familiar2_telefono,
        ];
    }

    // PERSONA MORAL

    private static function mapearPersonaMoral($req, $rent): array
    {
        return [
            'tipo_persona'      => 'Persona moral',
            'datosEmpresa'      => self::getDatosEmpresaMoral($req),
            'datosUsoPropiedad' => self::getUsoPropiedadMoral($req, $rent),
            'datosReferencias'  => self::getReferenciasMoral($req, $rent),
            'datosDocumentos'   => DocumentoMapper::mapear($rent->tenantDocuments, 'Inquilino', 'PM')
        ];
    }

    private static function getDatosEmpresaMoral($req): array
    {
        $facultadesEnActa = (int)$req->facultades_en_acta === 0 ? 'Sí' : 'No';

        return [
            // DATOS OBLIGATORIOS BASE
            'emailE'               => $req->email,
            'regimenFiscalPm'      => $req->regimen_fiscal,
            'emailRepLeg'          => $req->apoderado_email,
            'mismoDomicilioFiscal' => strtolower($req->mismo_domicilio_fiscal) === 'si' ? 1 : 0,
            'facultadEmp'          => $facultadesEnActa,
            'sexoRepLegal'         => ucfirst(strtolower($req->apoderado_sexo)), // "Masculino" o "Femenino"
            'ingMensualEmp'        => $req->ingreso_mensual_promedio ? (float) $req->ingreso_mensual_promedio : 0,

            // INFORMACIÓN DE LA EMPRESA
            'nombreEmp'            => $req->razon_social,
            'rfcfis'               => $req->rfc,
            'sitioWebEmp'          => $req->dominio_internet,
            'telefonoE'            => $req->telefono,

            // DOMICILIO ACTUAL DE LA EMPRESA
            'calleEmp'             => $req->calle,
            'numExtEmp'            => $req->numero_exterior,
            'numIntEmp'            => $req->numero_interior,
            'coloniaEmp'           => $req->colonia,
            'municipioEmp'         => $req->municipio,
            'estadoEmp'            => $req->estado,
            'cpostalEmp'           => $req->codigo_postal,
            'referenciaDomiEmp'    => $req->referencias_ubicacion,

            // DOMICILIO FISCAL (Si es diferente)
            'calleFiscal'          => $req->calle_fiscal,
            'numExtFiscal'         => $req->numero_exterior_fiscal,
            'numIntFiscal'         => $req->numero_interior_fiscal,
            'cpFiscal'             => $req->codigo_postal_fiscal,
            'coloniaFiscal'        => $req->colonia_fiscal,
            'munFiscal'            => $req->municipio_fiscal,
            'estadoFiscal'         => $req->estado_fiscal,

            // ACTA CONSTITUTIVA
            'nombreNotario'        => $req->notario_nombres,
            'apellidoPNot'         => $req->notario_primer_apellido,
            'apellidoMNot'         => $req->notario_segundo_apellido,
            'escritura'            => $req->numero_escritura,
            'fechaConst'           => $req->fecha_constitucion ? \Carbon\Carbon::parse($req->fecha_constitucion)->format('Y-m-d') : null,
            'notario'              => $req->notario_numero,
            'ciudadRegistro'       => $req->ciudad_registro,
            'estadoRegistro'       => $req->estado_registro,
            'numRegInsc_acta'      => $req->numero_registro_inscripcion,
            'giro'                 => $req->giro_comercial,

            // APODERADO LEGAL
            'nombreRazon'          => $req->apoderado_nombres,
            'apellidoPe'           => $req->apoderado_primer_apellido,
            'apellidoMe'           => $req->apoderado_segundo_apellido,
            'telefonoEmpRepLeg'    => $req->apoderado_telefono,
            'extRepLeg'            => $req->apoderado_extension,

            // FACULTADES EN ACTA (Si contestó "No" = 1)
            'numEscActaLeg'        => $req->escritura_publica_numero,
            'numNotarioFacuEmp'    => $req->notario_numero_facultades,
            'fechaEscLeg'          => $req->fecha_escritura_facultades ? \Carbon\Carbon::parse($req->fecha_escritura_facultades)->format('Y-m-d') : null,
            'r_ciudadRegistro'     => $req->ciudad_registro_facultades,
            'r_estadoRegistro'     => $req->estado_registro_facultades,
            'numInscripcion'       => $req->numero_inscripcion_registro_publico,
            'fechaInscrpReg'       => $req->fecha_inscripcion_facultades ? \Carbon\Carbon::parse($req->fecha_inscripcion_facultades)->format('Y-m-d') : null,
            'tipoPrestacionLeg'    => $req->tipo_representacion,
            'otroTipoPres'         => $req->tipo_representacion_otro,
        ];
    }

    private static function getUsoPropiedadMoral($req, $rent): array
    {
        $esResidencial = $rent->tipo_inmueble === 'residencial';
        $tipoInmueble = $esResidencial ? 'Inmuebles Residenciales' : 'Inmuebles Comerciales';

        $datos = [
            'tipoInmueble_PM' => $tipoInmueble,
        ];

        // INMUEBLE RESIDENCIAL (Persona Moral)
        if ($esResidencial) {
            
            $datos = array_merge($datos, [
                'adultos_PM'     => $req->numero_adultos ? (int) $req->numero_adultos : 0,
                //  0=Sí, 1=No para menores
                'menores_PM'     => $req->tiene_menores == 1 ? 0 : 1, 
                //  1=Sí, 0=No para mascotas
                'mascotas_PM'    => $req->tiene_mascotas == 1 ? 1 : 0, 
                
                // Opcionales Residencial
                'adulto1_PM'     => $req->nombre_adulto_1,
                'adulto2_PM'     => $req->nombre_adulto_2,
                'adulto3_PM'     => $req->nombre_adulto_3,
                'adulto4_PM'     => $req->nombre_adulto_4,
                'cuantos_PM'     => $req->tiene_menores == 1 ? (int) $req->cuantos_menores : null,
                'especifique_PM' => $req->tiene_mascotas == 1 ? $req->especificar_mascotas : null,
            ]);

        } 
        // INMUEBLE COMERCIAL (Persona Moral)
        else {
            $sustituyeDomicilio = $req->sustituye_otro_domicilio == 1 ? 'Sí' : 'No';

            $datos = array_merge($datos, [
                'uso_sustituirDomicilio_PM' => $sustituyeDomicilio,
                
                // Opcionales Comercial
                'giroNegocio_PM'            => $req->giro_negocio,
                'experienciaGiro_PM'        => $req->experiencia_giro,
                'uso_proposito_PM'          => $req->propositos_arrendamiento,
                
                // Domicilio anterior (Si sustituye domicilio)
                'uso_calle_PM'              => $req->domicilio_anterior_calle,
                'uso_numExt_PM'             => $req->domicilio_anterior_numero_exterior,
                'uso_numInt_PM'             => $req->domicilio_anterior_numero_interior,
                'uso_codigoPos_PM'          => $req->domicilio_anterior_codigo_postal,
                'uso_colonia_PM'            => $req->domicilio_anterior_colonia,
                'uso_municipio_PM'          => $req->domicilio_anterior_delegacion_municipio,
                'uso_estado_PM'             => $req->domicilio_anterior_estado,
                'uso_motivo_PM'             => $req->motivo_cambio_domicilio,
            ]);
        }

        return $datos;
    }

    private static function getReferenciasMoral($req, $rent): array
    {
        return [
            // REFERENCIA COMERCIAL 1
            'contacto1_nombre_empresa'  => $req->referencia_comercial1_empresa,
            'contacto1_nombre_contacto' => $req->referencia_comercial1_contacto,
            'contacto1_telefono'        => $req->referencia_comercial1_telefono,

            // REFERENCIA COMERCIAL 2
            'contacto2_nombre_empresa'  => $req->referencia_comercial2_empresa,
            'contacto2_nombre_contacto' => $req->referencia_comercial2_contacto,
            'contacto2_telefono'        => $req->referencia_comercial2_telefono,

            // REFERENCIA COMERCIAL 3
            'contacto3_nombre_empresa'  => $req->referencia_comercial3_empresa,
            'contacto3_nombre_contacto' => $req->referencia_comercial3_contacto,
            'contacto3_telefono'        => $req->referencia_comercial3_telefono,
        ];
    }
}