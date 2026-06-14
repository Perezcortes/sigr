<?php

namespace App\Services\PdrApi\Mappers;

use Carbon\Carbon;
use App\Services\PdrApi\Mappers\DocumentoMapper;

class FiadorMapper
{
    /**
     * Punto de entrada principal para mapear cualquier Fiador/Obligado Solidario
     */
    public static function mapear($guarantorRequest, $rentRecord): array
    {
        if ($guarantorRequest->tipo_persona === 'fisica') {
            return self::mapearPersonaFisica($guarantorRequest, $rentRecord);
        }

        return self::mapearPersonaMoral($guarantorRequest, $rentRecord);
    }

    /**
     * =========================================================================
     * MAPEADORES: PERSONA FÍSICA
     * =========================================================================
     */
    private static function mapearPersonaFisica($req, $rent): array
    {
        return [
            'incluyeFiadorFlag'      => 1, // Nuevo campo raíz requerido por Jona
            'tipo_persona'           => 'Persona física',
            'tipo_fiador'            => 'Fiador', // Nuevo campo requerido por Jona
            'datosPersonales'        => self::getDatosPersonalesFisica($req, $rent),
            'datosEmpleoIngresos'    => self::getDatosEmpleoFisica($req),
            'datosPropiedadGarantia' => self::getPropiedadGarantia($req),
            'datosDocumentos'        => DocumentoMapper::mapear($rent->guarantorDocuments, 'Fiador', 'PF'),
        ];
    }

    private static function getDatosPersonalesFisica($req, $rent): array
    {
        $nacionalidadf = strtolower($req->nacionalidad) === 'extranjera' ? 1 : 0;
        $sexof         = strtolower($req->sexo) === 'femenino' ? 1 : 2;
        $edoCivilf     = strtolower($req->estado_civil) === 'casado' ? 1 : 2;
        
        $identif = $req->tipo_identificacion;
        if ($identif === 'Cedula') {
            $identif = 'Cédula';
        }

        $datos = [
            'emailf'                 => $req->email,
            'fechaNacf'              => $req->fecha_nacimiento ? Carbon::parse($req->fecha_nacimiento)->format('Y-m-d') : null,
            'mismoDomicilioFiscal'   => (int) $req->es_domicilio_fiscal,
            'nacionalidadf'          => $nacionalidadf,
            'sexof'                  => $sexof,
            'edoCivilf'              => $edoCivilf,

            'nombref'                => $req->nombres,
            'apellidoPf'             => $req->primer_apellido,
            'apellidoMf'             => $req->segundo_apellido,
            'telefonof'              => $req->telefono_fijo,
            'celularf'               => $req->telefono_celular,
            'relacionf'              => $req->relacion_solicitante,
            'tiempof'                => $req->tiempo_conocerlo,
            
            'nombreCony'             => $edoCivilf === 1 ? $req->conyuge_nombres : null,
            'apellidoPcony'          => $edoCivilf === 1 ? $req->conyuge_primer_apellido : null,
            'apellidoMcony'          => $edoCivilf === 1 ? $req->conyuge_segundo_apellido : null,
            'telefonoCony'           => $edoCivilf === 1 ? $req->conyuge_telefono : null,

            'identif'                => $identif,
            'curpf'                  => $req->curp,
            'rfcf'                   => $req->rfc,
            
            'callef'                 => $req->calle,
            'numExtf'                => $req->numero_exterior,
            'numIntf'                => $req->numero_interior,
            'coloniaf'               => $req->colonia,
            'munif'                  => $req->municipio,
            'estadof'                => $req->estado,
            'cpf'                    => $req->codigo_postal,
            'metrosCuadradosActualf' => $req->metros_cuadrados,

            'calleFiscal'            => (int)$req->es_domicilio_fiscal === 0 ? $req->fiscal_calle : null,
            'numExtFiscal'           => (int)$req->es_domicilio_fiscal === 0 ? $req->fiscal_numero_exterior : null,
            'numIntFiscal'           => (int)$req->es_domicilio_fiscal === 0 ? $req->fiscal_numero_interior : null,
            'cpFiscal'               => (int)$req->es_domicilio_fiscal === 0 ? $req->fiscal_codigo_postal : null,
            'coloniaFiscal'          => (int)$req->es_domicilio_fiscal === 0 ? $req->fiscal_colonia : null,
            'munFiscal'              => (int)$req->es_domicilio_fiscal === 0 ? $req->fiscal_municipio : null,
            'estadoFiscal'           => (int)$req->es_domicilio_fiscal === 0 ? $req->fiscal_estado : null,
        ];

        if ($nacionalidadf === 1) { 
            if ($rent->tipo_poliza === 'PÓLIZA INTEGRAL' || $rent->tipo_poliza === 'PÓLIZA AMPLIA') {
                $datos['especifiquef'] = $req->nacionalidad_especifica;
            } elseif ($rent->tipo_poliza === 'PÓLIZA CON SEGURO') {
                $datos['paispf'] = $req->pais_origen;
                $datos['fechaVencimientoTarjetaf'] = $req->fecha_vencimiento_tarjeta ? Carbon::parse($req->fecha_vencimiento_tarjeta)->format('Y-m-d') : null;
                $datos['nuef'] = $req->nue;
                $datos['tipoResidenciaf'] = ucfirst(strtolower($req->tipo_residencia)); 
            }
        }

        return $datos;
    }

    private static function getDatosEmpleoFisica($req): array
    {
        return [
            'regimenFiscalPf' => $req->regimen_fiscal,
            'ingresof'        => $req->ingreso_mensual,
            'autorizo'        => $req->autoriza_buro ? 1 : 0,
            'declaro'         => $req->acepta_protesta ? 1 : 0,

            'fechaIngf'       => $req->fecha_ingreso_empleo ? Carbon::parse($req->fecha_ingreso_empleo)->format('Y-m-d') : null,
            'tipoEmpleof'     => $req->tipo_empleo,
            'profesionf'      => $req->profesion_puesto,
            
            'empresaf'        => $req->empresa_trabaja,
            'telefonoEf'      => $req->empresa_telefono,
            'extEf'           => $req->empresa_extension,
            
            'calleEf'         => $req->empresa_calle,
            'numExtEf'        => $req->empresa_numero_exterior,
            'numIntEf'        => $req->empresa_numero_interior,
            'cpEf'            => $req->empresa_codigo_postal,
            'coloniaEf'       => $req->empresa_colonia,
            'muniEf'          => $req->empresa_municipio,
            'estadoEf'        => $req->empresa_estado,
        ];
    }

    /**
     * =========================================================================
     * MAPEADORES: PERSONA MORAL
     * =========================================================================
     */
    private static function mapearPersonaMoral($req, $rent): array
    {
        return [
            'incluyeFiadorFlag'      => 1, // Nuevo campo raíz requerido por Jona
            'tipo_persona'           => 'Persona moral',
            'tipo_fiador'            => 'Fiador', // Nuevo campo requerido por Jona
            'datosEmpresa'           => self::getDatosEmpresaMoral($req),
            'datosPropiedadGarantia' => self::getPropiedadGarantia($req),
            'datosDocumentos'        => DocumentoMapper::mapear($rent->guarantorDocuments, 'Fiador', 'PM'),
        ];
    }

    /**
     * Mapea el mega-bloque de la Empresa de la Persona Moral
     */
    private static function getDatosEmpresaMoral($req): array
    {
        // Adaptación de booleanos/radios a los valores estrictos de la API
        $mismoDomicilioFiscal = (int)$req->es_domicilio_fiscal;
        $facultadesEnActa     = (int)$req->facultades_en_acta === 1 ? 'Sí' : 'No';

        return [
            // Obligatorios Base
            'regimenFiscalPm'              => $req->regimen_fiscal,
            'mismoDomicilioFiscal'         => $mismoDomicilioFiscal,

            // Información General de la Empresa
            'email_moral'                  => $req->email,
            'telefono'                     => $req->telefono,
            'ingresos_aprox_empresa'       => $req->ingreso_mensual,
            'rfc_moral'                    => $req->rfc,
            'nombre_empresa_moral'         => $req->razon_social,
            'antiguedad_empresa'           => $req->antiguedad_empresa,
            'actividades_ingresos_empresa' => $req->actividades_empresa,

            // Domicilio de la Empresa
            'direccion_empresa'            => $req->calle, // Jona mapea la calle principal aquí
            'numExtEmp_moral'              => $req->numero_exterior,
            'numIntEmp_moral'              => $req->numero_interior,
            'cpEmp_moral'                  => $req->codigo_postal,
            'coloniaEmp_moral'             => $req->colonia,
            'municipioEmp_moral'           => $req->municipio,
            'estadoEmp_moral'              => $req->estado,

            // Datos del Acta Constitutiva
            'nombresNotario'               => $req->notario_nombres,
            'apellidoPNot'                 => $req->notario_apellidos, 
            'apellidoMNot'                 => null, 
            'numEscrituraEmp'              => $req->numero_escritura,
            'fechaConstituEmp'             => $req->fecha_constitucion ? Carbon::parse($req->fecha_constitucion)->format('Y-m-d') : null,
            'numNotario'                   => $req->notario_numero,
            'ciudadReg'                    => $req->ciudad_registro,
            'estadoReg'                    => $req->estado_registro,
            'numRegistro'                  => $req->numero_inscripcion_pm,
            'giroComercial'                => $req->giro_comercial,

            // Información sobre el Representante Legal
            'r_nombre_completo'            => $req->rep_nombres,
            'apellidoPrep_moral'           => $req->rep_primer_apellido,
            'apellidoMrep_moral'           => $req->rep_segundo_apellido,
            'sexoRepLegObliFiad'           => $req->rep_sexo,
            'rfcRep_moral'                 => $req->rep_rfc,
            'r_curp'                       => $req->rep_curp,
            'r_correo_electronico'         => $req->rep_email,
            'r_telefono'                   => $req->rep_telefono,
            
            // Domicilio del Representante Legal
            'r_direccion'                  => $req->rep_calle,
            'numExtRep_moral'              => $req->rep_numero_exterior,
            'numIntRep_moral'              => $req->rep_numero_interior,
            'cpEmpRep_moral'               => $req->rep_codigo_postal,
            'coloniaRep_moral'             => $req->rep_colonia,
            'municipioRep_moral'           => $req->rep_municipio,
            'estadoRep_moral'              => $req->rep_estado,

            // Facultades Legales (Condicionales si facultades_en_acta === 0)
            'r_facultadEmp'                => $facultadesEnActa,
            'r_numEscActaLeg'              => $facultadesEnActa === 'No' ? $req->fac_escritura : null,
            'r_numNotarioFacuEmp'          => $facultadesEnActa === 'No' ? $req->fac_notario : null,
            'r_fechaEscLeg'                => $facultadesEnActa === 'No' && $req->fac_fecha_escritura ? Carbon::parse($req->fac_fecha_escritura)->format('Y-m-d') : null,
            'r_numInscripcion'             => $facultadesEnActa === 'No' ? $req->fac_inscripcion : null,
            'r_fechaInscrip'               => $facultadesEnActa === 'No' && $req->fac_fecha_inscripcion ? Carbon::parse($req->fac_fecha_inscripcion)->format('Y-m-d') : null,
            'r_ciudadRegistro'             => $facultadesEnActa === 'No' ? $req->fac_ciudad : null,
            'r_estadoRegistro'             => $facultadesEnActa === 'No' ? $req->fac_estado : null,
            'r_tipoPrestacionLeg'          => $facultadesEnActa === 'No' ? $req->fac_tipo_representacion : null,
            'r_otroTipoPres'               => $facultadesEnActa === 'No' && $req->fac_tipo_representacion === 'Otro' ? $req->fac_representacion_otro : null,

            // Domicilio Fiscal (Solo se envía si es diferente)
            'calleFiscal'                  => $mismoDomicilioFiscal === 0 ? $req->fiscal_calle : null,
            'numExtFiscal'                 => $mismoDomicilioFiscal === 0 ? $req->fiscal_numero_exterior : null,
            'numIntFiscal'                 => $mismoDomicilioFiscal === 0 ? $req->fiscal_numero_interior : null,
            'cpFiscal'                     => $mismoDomicilioFiscal === 0 ? $req->fiscal_codigo_postal : null,
            'coloniaFiscal'                => $mismoDomicilioFiscal === 0 ? $req->fiscal_colonia : null,
            'munFiscal'                    => $mismoDomicilioFiscal === 0 ? $req->fiscal_municipio : null,
            'estadoFiscal'                 => $mismoDomicilioFiscal === 0 ? $req->fiscal_estado : null,
        ];
    }

    /**
     * Mapea la sección "Propiedad en Garantía" (Común para Física y Moral)
     */
    private static function getPropiedadGarantia($req): array
    {
        return [
            'fechaf'         => $req->garantia_fecha_escritura ? Carbon::parse($req->garantia_fecha_escritura)->format('Y-m-d') : null,
            'fechaRegistro'  => $req->garantia_fecha_rpp ? Carbon::parse($req->garantia_fecha_rpp)->format('Y-m-d') : null,
            
            'calleDf'        => $req->garantia_calle,
            'numExtDf'       => $req->garantia_numero_exterior,
            'numIntDf'       => $req->garantia_numero_interior,
            'cpDf'           => $req->garantia_codigo_postal,
            'coloniaDf'      => $req->garantia_colonia,
            'muniDf'         => $req->garantia_municipio,
            'estadoDf'       => $req->garantia_estado,
            
            'escrituraf'     => $req->garantia_num_escritura,
            'notariaf'       => $req->garantia_notario_nombres,
            'apellidoPnotpf' => $req->garantia_notario_paterno,
            'apellidoMnotpf' => $req->garantia_notario_materno,
            'notariafn'      => $req->garantia_num_notaria,
            'notariaEstado'  => $req->garantia_lugar_notaria,
            
            'registro'       => $req->garantia_rpp,
            'foliof'         => $req->garantia_folio_real,
            'boletaf'        => $req->garantia_boleta_predial,
        ];
    }
}