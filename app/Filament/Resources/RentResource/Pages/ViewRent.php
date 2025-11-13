<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\RentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewRent extends ViewRecord
{
    protected static string $resource = RentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // SECCIÓN: DATOS GENERALES
                Infolists\Components\Section::make('Datos generales')
                    ->schema([
                        Infolists\Components\TextEntry::make('folio')
                            ->label('Folio')
                            ->default('RPG-3681-2025'),
                        
                        Infolists\Components\TextEntry::make('sucursal')
                            ->label('Sucursal')
                            ->default('Póliza de Rentas R&PG Consultores'),
                        
                        Infolists\Components\TextEntry::make('abogado')
                            ->label('Abogado*')
                            ->default('Ana Virinia Pérez Guemes y Ocampo'),
                        
                        Infolists\Components\TextEntry::make('inmobiliaria')
                            ->label('Inmobiliaria*')
                            ->default('Selecciona una inmobiliaria'),
                        
                        Infolists\Components\TextEntry::make('estatus')
                            ->label('Estatus')
                            ->badge()
                            ->color('success')
                            ->default('Nueva'),
                        
                        Infolists\Components\TextEntry::make('tipo_inmueble')
                            ->label('Tipo de inmueble*')
                            ->default('Inmuebles Residenciales'),
                        
                        Infolists\Components\TextEntry::make('tipo_poliza')
                            ->label('Tipo de póliza')
                            ->default('PÓLIZA CON SEGURO'),
                        
                        Infolists\Components\TextEntry::make('renta')
                            ->label('Renta')
                            ->money('MXN')
                            ->default(13500),
                        
                        Infolists\Components\TextEntry::make('poliza')
                            ->label('Póliza')
                            ->money('MXN')
                            ->default(0),
                    ])
                    ->columns(2)
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('guardar_general')
                            ->label('Guardar')
                            ->color('primary')
                            ->icon('heroicon-o-check'),
                        Infolists\Components\Actions\Action::make('cancelar')
                            ->label('Cancelar')
                            ->color('gray')
                            ->icon('heroicon-o-x-mark'),
                        Infolists\Components\Actions\Action::make('clonar_renta')
                            ->label('Clonar renta')
                            ->color('success')
                            ->icon('heroicon-o-document-duplicate'),
                    ]),

                // SECCIÓN: INQUILINO
                Infolists\Components\Section::make('Inquilino')
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.tipo_persona')
                            ->label('Tipo de Persona:')
                            ->formatStateUsing(fn ($state) => $state === 'fisica' ? 'Persona física' : 'Persona moral'),
                        
                        Infolists\Components\TextEntry::make('tenant.nombres')
                            ->label('Nombre:'),
                        
                        Infolists\Components\TextEntry::make('tenant.primer_apellido')
                            ->label('Primer Apellido:'),
                        
                        Infolists\Components\TextEntry::make('tenant.segundo_apellido')
                            ->label('Segundo Apellido:'),
                        
                        Infolists\Components\TextEntry::make('tenant.sexo')
                            ->label('Sexo:')
                            ->default('Seleccione'),
                        
                        Infolists\Components\TextEntry::make('tenant.email')
                            ->label('Correo:'),
                    ])
                    ->columns(2)
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('edit_tenant')
                            ->label('Editar solicitud del inquilino')
                            ->color('primary'),
                        Infolists\Components\Actions\Action::make('send_tenant')
                            ->label('Enviar solicitud al inquilino')
                            ->color('success'),
                        Infolists\Components\Actions\Action::make('copy_link_tenant')
                            ->label('Copiar link')
                            ->color('gray'),
                        Infolists\Components\Actions\Action::make('export_pdf_tenant')
                            ->label('Exportar PDF')
                            ->color('warning'),
                    ]),

                // SECCIÓN: FIADOR
                Infolists\Components\Section::make('Fiador')
                    ->schema([
                        Infolists\Components\TextEntry::make('tiene_fiador')
                            ->label('¿Tiene fiador?')
                            ->default('No'),
                    ])
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('edit_guarantor')
                            ->label('Editar solicitud del fiador')
                            ->color('primary'),
                        Infolists\Components\Actions\Action::make('send_guarantor')
                            ->label('Enviar solicitud al fiador')
                            ->color('success'),
                        Infolists\Components\Actions\Action::make('copy_link_guarantor')
                            ->label('Copiar link')
                            ->color('gray'),
                    ]),

                // SECCIÓN: PROPIETARIO
                Infolists\Components\Section::make('Propietario')
                    ->schema([
                        Infolists\Components\TextEntry::make('owner.tipo_persona')
                            ->label('Tipo de Persona:')
                            ->formatStateUsing(fn ($state) => $state === 'fisica' ? 'Persona física' : 'Persona moral'),
                        
                        Infolists\Components\TextEntry::make('owner.nombres')
                            ->label('Nombre:'),
                        
                        Infolists\Components\TextEntry::make('owner.primer_apellido')
                            ->label('Primer Apellido:'),
                        
                        Infolists\Components\TextEntry::make('owner.segundo_apellido')
                            ->label('Segundo Apellido:'),
                        
                        Infolists\Components\TextEntry::make('owner.sexo')
                            ->label('Sexo:')
                            ->default('Seleccione'),
                        
                        Infolists\Components\TextEntry::make('owner.email')
                            ->label('Correo:'),
                    ])
                    ->columns(2)
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('edit_owner')
                            ->label('Editar solicitud del propietario')
                            ->color('primary'),
                        Infolists\Components\Actions\Action::make('send_owner')
                            ->label('Enviar solicitud al propietario')
                            ->color('success'),
                        Infolists\Components\Actions\Action::make('copy_link_owner')
                            ->label('Copiar link')
                            ->color('gray'),
                        Infolists\Components\Actions\Action::make('export_pdf_owner')
                            ->label('Exportar PDF')
                            ->color('warning'),
                    ]),

                // SECCIÓN: PROPIEDAD
                Infolists\Components\Section::make('Propiedad')
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo_propiedad')
                            ->label('Tipo de Propiedad')
                            ->default('Seleccione'),
                        
                        Infolists\Components\TextEntry::make('calle')
                            ->label('Calle')
                            ->default(''),
                        
                        Infolists\Components\TextEntry::make('numero_exterior')
                            ->label('Núm Ext')
                            ->default(''),
                        
                        Infolists\Components\TextEntry::make('numero_interior')
                            ->label('Núm Int')
                            ->default(''),
                        
                        Infolists\Components\TextEntry::make('referencias_ubicacion')
                            ->label('Referencias Ubicación')
                            ->default(''),
                        
                        Infolists\Components\TextEntry::make('colonia')
                            ->label('Colonia')
                            ->default(''),
                        
                        Infolists\Components\TextEntry::make('municipio')
                            ->label('Municipio/Alcaldía')
                            ->default(''),
                        
                        Infolists\Components\TextEntry::make('estado')
                            ->label('Estado')
                            ->default('Seleccione'),
                        
                        Infolists\Components\TextEntry::make('codigo_postal')
                            ->label('CP')
                            ->default(''),
                    ])
                    ->columns(2)
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('guardar_propiedad')
                            ->label('Guardar')
                            ->color('primary')
                            ->icon('heroicon-o-check'),
                    ]),
            ]);
    }
}