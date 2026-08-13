<?php
    $authUrl = $authUrl ?? route('customer.authorize', ['booking' => $booking->id, 'hash' => \App\Http\Controllers\CustomerAuthController::generateHash($booking)]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $customSubject ?? 'Autorización de Pago y Confirmación de Reserva' }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #222222; margin: 0; padding: 20px 10px; line-height: 1.5; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    
    <!-- MAIN WRAPPER TABLE -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f4f6f9; width: 100%; margin: 0; padding: 0;">
        <tr>
            <td align="center" style="padding: 10px 0;">
                
                <!-- CONTAINER TABLE (680px max) -->
                <table width="680" border="0" cellpadding="0" cellspacing="0" style="max-width: 680px; width: 100%; background-color: #ffffff; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #dcdfe6; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    
                    <!-- HEADER SECTION -->
                    <tr>
                        <td align="center" style="background-color: #1e293b; color: #ffffff; padding: 24px 30px; text-align: center;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; font-family: Arial, sans-serif;">Autorización de Pago y Confirmación de Reserva</h1>
                            <div style="margin-top: 8px; font-size: 13px; color: #94a3b8; font-family: Arial, sans-serif;">
                                Ref: <strong style="color: #ffffff;">#{{ $booking->booking_id }}</strong> &nbsp;|&nbsp; 
                                PNR: <strong style="color: #ffffff;">{{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}</strong>
                            </div>
                        </td>
                    </tr>

                    <!-- CONTENT BODY -->
                    <tr>
                        <td style="padding: 25px 30px; background-color: #ffffff;">
                            
                            <!-- SALUTATION & GREETING -->
                            <div style="font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 6px; font-family: Arial, sans-serif;">
                                Estimado/a {{ $booking->card_holder_name ?: 'Cliente' }},
                            </div>
                            <div style="font-size: 14px; color: #334155; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                ¡¡ Un cordial saludo !!
                            </div>

                            <!-- SERVICE STATEMENT -->
                            @php
                                $airlineName = $booking->airline_name ?: ($booking->airline_code ?: 'American Airlines');
                                $pnr = $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A');
                                $merchantName = $booking->merchant ?: 'Travelomile';
                                $serviceProvidedTextEs = match($booking->service_provided) {
                                    'exchange' => 'procesado el cambio de itinerario',
                                    'cancellation' => 'procesado la cancelación',
                                    'refund' => 'procesado el reembolso',
                                    'seat_selection' => 'seleccionado los asientos',
                                    'baggage_addition' => 'agregado el equipaje',
                                    'others' => 'procesado su solicitud de servicio',
                                    'cancel_and_refund' => 'procesado la cancelación y reembolso',
                                    'name_correction' => 'procesado la corrección de nombre',
                                    'flight_upgrade' => 'procesado la mejora de clase de vuelo',
                                    'dob_correction' => 'procesado la corrección de fecha de nacimiento',
                                    'pet_in_cabin' => 'agregado la mascota en cabina',
                                    'ancillary_refund' => 'procesado el reembolso de servicios adicionales',
                                    'infant_ticket' => 'procesado el boleto de infante',
                                    default => 'reservado sus vuelos'
                                };
                            @endphp

                            <div style="font-size: 14px; color: #334155; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                Según nuestra conversación telefónica y lo acordado, hemos {{ $serviceProvidedTextEs }} para su reserva con {{ $airlineName }} bajo la Confirmación <strong style="color: #0f172a;">{{ $pnr }}</strong>. Por favor, consulte los detalles a continuación.
                            </div>

                            <div style="font-weight: bold; font-size: 14px; margin-bottom: 16px; color: #0f172a; font-family: Arial, sans-serif;">
                                Costo total para todos los pasajeros: {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }} (impuestos y cargos incluidos).
                            </div>

                            <!-- LEGAL AUTHORIZATION DECLARATION BOX -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-left: 4px solid #4f46e5; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; border-radius: 4px; margin: 18px 0;">
                                <tr>
                                    <td style="padding: 14px 18px; font-size: 13px; color: #334155; line-height: 1.6; font-family: Arial, sans-serif;">
                                        <p style="margin: 0 0 10px 0;">
                                            Según nuestra conversación telefónica, yo, <strong style="color: #0f172a;">{{ $booking->card_holder_name ?: 'Cliente' }}</strong>, autorizo a {{ $airlineName }} / {{ $merchantName }} a procesar los cargos mencionados anteriormente bajo sus respectivos comercios para cargar mi tarjeta <strong style="color: #0f172a;">{{ $booking->card_type ?? 'Tarjeta'}}</strong>&nbsp;<strong style="color: #0f172a;">******{{ $booking->card_last_4 ?: 'XXXX' }}</strong> por la reserva del itinerario mencionado a continuación con {{ $airlineName }}.
                                        </p>
                                        <p style="margin: 0 0 10px 0;">
                                            Esta autorización de pago es por el monto indicado anteriormente y es válida para un solo uso. Certifico que soy <strong style="color: #0f172a;">{{ $booking->card_holder_name ?: 'Cliente' }}</strong>, un usuario autorizado de esta tarjeta y que no disputaré el pago con mi compañía de tarjeta de crédito/débito o banco.
                                        </p>
                                        <p style="margin: 0; font-weight: bold; color: #4f46e5;">
                                            Por favor confirme su aceptación de los términos y acuerdo con la declaración respondiendo a este correo electrónico con 'Estoy de acuerdo' o 'Autorizo'.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CHARGES DESCRIPTION -->
                            @php
                                $paidToAirline = floatval($booking->paid_to_airline ?? 0);
                                $agencyFee = max(0, floatval($booking->total_amount ?? 0) - $paidToAirline);
                            @endphp

                            @if($paidToAirline > 0)
                                <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                    Descripción de Cargos:
                                </div>
                                <div style="font-size: 13px; margin-bottom: 6px; color: #1e293b; font-family: Arial, sans-serif;">
                                    Cargo 1: <strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($paidToAirline, 2) }}</strong> ({{ $airlineName }}, incl. tarifa base)
                                </div>
                                @if($agencyFee > 0)
                                    <div style="font-size: 13px; margin-bottom: 16px; color: #1e293b; font-family: Arial, sans-serif;">
                                        Cargo 2: <strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($agencyFee, 2) }}</strong> ({{ $merchantName }}, incl. impuestos y cargos)
                                    </div>
                                @endif
                            @else
                                <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                    Descripción de Cargos
                                </div>
                                <div style="font-size: 13px; margin-bottom: 16px; color: #1e293b; font-family: Arial, sans-serif;">
                                    1. <strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</strong> ({{ $merchantName }}, impuestos y cargos incluidos)
                                </div>
                            @endif

                            <!-- PASSENGER DETAILS TABLE -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Detalles de Pasajeros
                            </div>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: #f1f5f9;">
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">N.º</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Tipo</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Nombre</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Segundo Nombre</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Apellido</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Género</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Fecha Nac.</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($booking->passengers as $idx => $pax)
                                        <tr>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $idx + 1 }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->pax_index ? 'ADT' : 'ADT' }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->first_name }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->middle_name ?: '-' }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->last_name }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->gender ? ($pax->gender == 'F' ? 'Femenino' : ($pax->gender == 'O' ? 'Otro' : 'Masculino')) : ($pax->title == 'MS' || $pax->title == 'MRS' ? 'Femenino' : 'Masculino') }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->dob ? ($pax->dob instanceof \DateTimeInterface ? $pax->dob->format('d M Y') : \Carbon\Carbon::parse($pax->dob)->format('d M Y')) : '-' }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->currency }} {{ number_format($booking->total_amount / max(count($booking->passengers), 1), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" style="padding: 12px; text-align: center; color: #64748b; border: 1px solid #e2e8f0;">No hay registros de pasajeros adjuntos.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        
                            <!-- FLIGHT ITINERARY CARDS -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Itinerario de Vuelo
                            </div>

                            @php
                                $emailFlightsEs = ($booking->flightSegments && $booking->flightSegments->isNotEmpty())
                                    ? $booking->flightSegments
                                    : $booking->bookingFlights;
                            @endphp

                            @forelse($emailFlightsEs as $flight)
                                @php
                                    $logoUrl = $flight->operated_by_logo ?: ($flight->airline_logo ?: null);
                                    if (!$logoUrl && !empty($flight->operated_by)) {
                                        $cleanOp = strtoupper(trim($flight->operated_by));
                                        if (strlen($cleanOp) === 2) {
                                            $logoUrl = "https://pics.avs.io/200/50/{$cleanOp}.png";
                                        } else {
                                            $nameMap = [
                                                'INDIGO' => '6E', 'AIR INDIA' => 'AI', 'SPICEJET' => 'SG', 'VISTARA' => 'UK',
                                                'AKASA' => 'QP', 'AMERICAN' => 'AA', 'UNITED' => 'UA', 'DELTA' => 'DL',
                                                'AIR FRANCE' => 'AF', 'BRITISH AIRWAYS' => 'BA', 'LUFTHANSA' => 'LH',
                                                'EMIRATES' => 'EK', 'ETIHAD' => 'EY', 'QATAR' => 'QR', 'KLM' => 'KL',
                                            ];
                                            foreach ($nameMap as $k => $v) {
                                                if (str_contains($cleanOp, $k)) {
                                                    $logoUrl = "https://pics.avs.io/200/50/{$v}.png";
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if (!$logoUrl && !empty($flight->operating_carrier) && strlen(trim($flight->operating_carrier)) === 2) {
                                        $logoUrl = "https://pics.avs.io/200/50/" . strtoupper(trim($flight->operating_carrier)) . ".png";
                                    }
                                @endphp
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 14px; border-collapse: separate; overflow: hidden; font-family: Arial, sans-serif;">
                                    <!-- Flight Header Row -->
                                    <tr>
                                        <td style="background-color: #f8fafc; padding: 10px 14px; font-size: 12px; border-bottom: 1px solid #e2e8f0;">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td align="left" style="font-size: 12px; color: #334155;">
                                                        <strong style="color: #0f172a;">{{ $flight->departure_time ? $flight->departure_time->format('D, d M') : '' }}</strong>
                                                        <span style="background-color: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 10px; font-weight: bold; font-size: 10px; margin-left: 6px; display: inline-block;">{{ $flight->status ? ($flight->status == 'Confirmed' ? 'Confirmado' : $flight->status) : 'Confirmado' }}</span>
                                                        <strong style="margin-left: 6px; color: #0f172a;">{{ $flight->airline_name ?: $flight->operating_carrier }} {{ $flight->operating_carrier }}{{ $flight->flight_number }}</strong>
                                                        <span style="color: #64748b; margin-left: 4px;">{{ $flight->cabin ?: 'Económica' }} ({{ $flight->booking_class ?: 'Y' }})</span>

                                                        @if(!empty($flight->operated_by))
                                                            <div style="font-size: 11px; color: #0284c7; font-weight: bold; margin-top: 4px;">
                                                                &#x2708;&#xFE0F; Operado por {{ $flight->operated_by }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td align="right" style="font-size: 12px; vertical-align: middle;">
                                                        @if($logoUrl)
                                                            <img src="{{ $logoUrl }}" alt="{{ $flight->operated_by ?: $flight->airline_name }}" height="22" border="0" style="height: 22px; max-width: 90px; vertical-align: middle; display: inline-block;">
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <!-- Flight Body Row -->
                                    <tr>
                                        <td style="padding: 14px; font-size: 13px; background-color: #ffffff;">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="42%" align="left" style="vertical-align: top;">
                                                        <div style="font-size: 20px; font-weight: 900; color: #0f172a; font-family: Arial, sans-serif;">{{ $flight->origin_airport }}</div>
                                                        <div style="font-weight: bold; margin-top: 2px; color: #1e293b; font-family: Arial, sans-serif;">{{ $flight->departure_time ? $flight->departure_time->format('h:i A') : '' }}</div>
                                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; font-family: Arial, sans-serif;">{{ $flight->origin_airport_name ?: ($flight->origin_city ?: $flight->origin_airport) }}</div>
                                                    </td>
                                                    <td width="16%" align="center" style="vertical-align: middle; color: #94a3b8; font-size: 18px; font-weight: bold;">
                                                        &#10140;
                                                    </td>
                                                    <td width="42%" align="right" style="vertical-align: top;">
                                                        <div style="font-size: 20px; font-weight: 900; color: #0f172a; font-family: Arial, sans-serif;">{{ $flight->destination_airport }}</div>
                                                        <div style="font-weight: bold; margin-top: 2px; color: #1e293b; font-family: Arial, sans-serif;">
                                                            {{ $flight->arrival_time ? $flight->arrival_time->format('h:i A') : '' }}
                                                            @if($flight->day_offset > 0)
                                                                <span style="color: #d97706; font-size: 10px;">(En +{{ $flight->day_offset }} Día)</span>
                                                            @endif
                                                        </div>
                                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; font-family: Arial, sans-serif;">{{ $flight->destination_airport_name ?: ($flight->destination_city ?: $flight->destination_airport) }}</div>
                                                    </td>
                                                </tr>
                                            </table>

                                            @if($flight->flight_duration || $flight->aircraft_type)
                                                <div style="font-size: 11px; color: #64748b; margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-family: Arial, sans-serif;">
                                                    @if($flight->flight_duration) <span>Duración: {{ $flight->flight_duration }}</span> @endif
                                                    @if($flight->aircraft_type) <span style="margin-left: 14px;">Aeronave: {{ $flight->aircraft_type }}</span> @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Transit Banner Row -->
                                    @if($flight->transit_text)
                                        <tr>
                                            <td align="center" style="background-color: #f1f5f9; padding: 6px 10px; font-size: 11px; font-weight: bold; color: #334155; border-top: 1px solid #e2e8f0; text-align: center;">
                                                &#x1F550; {{ $flight->transit_text }}
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            @empty
                                <p style="font-style: italic; color: #64748b; font-size: 13px;">No hay vuelos adjuntos a esta reserva.</p>
                            @endforelse

                                <!-- PURCHASE SUMMARY TABLE -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Resumen de Compra
                            </div>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                <tr>
                                    <td style="width: 35%; font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Tipo de Pago:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">Autorización de Tarjeta de Crédito/Débito</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Nombre del Titular:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_holder_name ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Tipo de Tarjeta:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_type ?: 'Tarjeta de Crédito/Débito' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Número de Tarjeta:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">XXXX-XXXX-XXXX-{{ $booking->card_last_4 ?: 'XXXX' }}</td>
                                </tr>
                                @if(!empty($booking->card_expiration))
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Vencimiento:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_expiration }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Dirección de Facturación:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->billing_address ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Teléfono de Contacto:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->calling_number ?: ($booking->billing_phone ?: 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Correo Electrónico:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->email_address ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Monto Total:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;"><strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Fecha de Transacción:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->created_at ? $booking->created_at->format('d/m/Y') : date('d/m/Y') }}</td>
                                </tr>
                            </table>

                            @if($booking->bookingCards && $booking->bookingCards->count() > 0)
                                <!-- MULTIPLE AUTHORIZED CARDS ROSTER -->
                                <div style="font-size: 13px; font-weight: bold; color: #1e1b4b; margin-top: 16px; margin-bottom: 8px; font-family: Arial, sans-serif; text-transform: uppercase;">
                                    Tarjetas de Pago Autorizadas ({{ $booking->bookingCards->count() + 1 }} Tarjetas)
                                </div>
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                    <thead>
                                        <tr style="background-color: #e0e7ff; color: #3730a3;">
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: left;">Titular</th>
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: left;">Tipo</th>
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: center;">Número</th>
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: center;">Vencimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; font-weight: bold;">{{ $booking->card_holder_name ?: 'Tarjeta Principal' }}</td>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_type ?: 'Tarjeta' }}</td>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">XXXX-XXXX-XXXX-{{ $booking->card_last_4 }}</td>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">{{ $booking->card_expiration ?: 'N/A' }}</td>
                                        </tr>
                                        @foreach($booking->bookingCards as $bCard)
                                            <tr>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $bCard->card_holder_name ?: 'Tarjeta Adicional' }}</td>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $bCard->card_type ?: 'Tarjeta' }}</td>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">XXXX-XXXX-XXXX-{{ $bCard->card_last_4 }}</td>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">{{ $bCard->card_expiration ?: 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif


                            <!-- IMPORTANT INFORMATION & TERMS -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 28px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Información Importante y Términos
                            </div>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.6; font-family: Arial, sans-serif;">
                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Por favor tenga en cuenta:</strong> Revise cuidadosamente los nombres, fechas, ciudades y horarios de salida/llegada.</p>
                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Importante:</strong> Sus boletos electrónicos le serán enviados por correo electrónico dentro de las 24 horas, o antes si no hay demoras por parte de la aerolínea. Tenga en cuenta que las tarifas no están garantizadas hasta que se reciba el pago y se emitan los boletos. Si hay alguna restricción, actualización o inquietud por parte de la aerolínea, nos pondremos en contacto con usted por correo electrónico o teléfono. Si desea realizar algún cambio en este itinerario después de emitidos los boletos, usted será responsable de penalizaciones adicionales, diferencias de tarifa y cargos aplicables.</p>
                                <p style="margin: 0 0 10px 0;">Pueden aplicarse cargos por equipaje. Por favor consulte con la aerolínea las políticas de equipaje más actualizadas.</p>
                                
                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Nota:</strong> Según lo acordado, su tarjeta de crédito puede ser cargada en transacciones divididas, sin exceder el monto total. Todas las transacciones corresponden a cargos de servicio y son 100% no reembolsables. Los boletos de avión no son reembolsables; sin embargo, puede ser elegible para un reembolso dentro de las 24 horas posteriores a la compra, según la política de la aerolínea.</p>

                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Descargo de responsabilidad:</strong> {{ $merchantName }} es una agencia de viajes independiente sin asociación con terceros. No nos asociamos ni seremos considerados como una aerolínea o un aliado de ninguna de las aerolíneas o marcas. {{ $merchantName }} se muestra en los detalles de su cuenta bancaria en la mayoría de los casos. Sin embargo, a veces debemos dividir el pago con la aerolínea. {{ $merchantName }} y la aerolínea u otra compañía de esa organización aparecerán como destinatarios en su cuenta. Todos los cargos de servicio y conveniencia no son reembolsables.</p>

                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Para asistencia:</strong> En caso de cualquier discrepancia o si se requiere una modificación, no dude en contactarnos al +1 888-476-0932 o por correo electrónico a reservation@travelomile.com dentro de las 24 horas y con gusto le asistiremos.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Información Importante:</h4>
                                <ul style="margin: 0 0 10px 0; padding-left: 20px;">
                                    <li style="margin-bottom: 4px;">Los nombres de los pasajeros deben coincidir exactamente con el pasaporte (Viajes Internacionales) O con una identificación oficial con foto aprobada por el gobierno para viajes nacionales.</li>
                                    <li style="margin-bottom: 4px;">Aconsejamos a todos los pasajeros asegurarse de tener todos los documentos de viaje, incluidos pasaportes y visados requeridos emitidos y presentados al momento de viajar.</li>
                                    <li style="margin-bottom: 4px;">Se recomienda a todos los pasajeros presentarse en el aeropuerto 3 horas antes de la salida para vuelos internacionales y 2 horas antes para vuelos nacionales.</li>
                                    <li style="margin-bottom: 4px;">Todos los vuelos internacionales deben reconfirmarse 72 horas antes de la salida.</li>
                                    <li style="margin-bottom: 4px;">Revise las fechas, horarios de salida/llegada, ciudades de origen/destino, escalas y conexiones.</li>
                                    <li style="margin-bottom: 4px;">Los boletos de avión no son reembolsables, no cambiables y no cancelables en la mayoría de los casos. Una aerolínea puede permitir cambiar un boleto por un cargo adicional más la diferencia de tarifa del nuevo boleto.</li>
                                </ul>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Consultas sobre Cambios:</h4>
                                <p style="margin: 0 0 10px 0;">Llámenos al +1 888-476-0932 para realizar cualquier tipo de cambio en el itinerario. Cualquier cambio en el itinerario debe realizarse antes de la salida del vuelo. Las reglas de la aerolínea se cotizarán al pasajero antes de procesar cualquier modificación al itinerario, lo cual incluirá penalización, tarifa de proveedor y diferencia de tarifa.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Cancelaciones y Reembolsos:</h4>
                                <p style="margin: 0 0 10px 0;">Llámenos al +1 888-476-0932. La reserva debe cancelarse al menos 24 horas antes de la hora programada de salida de su vuelo para evitar un no-show (no presentación). Las cancelaciones solo se pueden procesar por teléfono.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Asignación de Asientos:</h4>
                                <p style="margin: 0 0 10px 0;">La mayoría de las aerolíneas tienen reglas restringidas para la asignación anticipada de asientos y solo se pueden realizar mediante el pago de un cargo. Llámenos al +1 888-476-0932 para la asignación de asientos, si corresponde.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Política de Equipaje:</h4>
                                <p style="margin: 0 0 10px 0;">Su reserva puede tener una franquicia de equipaje restringida y algunas aerolíneas pueden cobrar una tarifa adicional por cada equipaje facturado o de mano permitido.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Visados y Documentos de Viaje:</h4>
                                <p style="margin: 0 0 10px 0;">Se aconseja a todos los clientes verificar sus documentos de viaje (visado de tránsito/visado de entrada) para el país por el que transitan o al que ingresan. Le solicitamos consultar con la embajada del país o países que visita o por los que transita.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Check-In (Registro):</h4>
                                <p style="margin: 0 0 10px 0;">Recomendamos llegar al aeropuerto 3 horas antes de la salida para vuelos internacionales y 2 horas antes para vuelos nacionales.</p>
                            </div>
                        </td>
                    </tr>

                    <!-- FOOTER & AGENT SIGNATURE -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0; font-family: Arial, sans-serif;">
                            <div style="font-weight: bold; margin-bottom: 4px; color: #1e293b;">Atentamente,</div>
                            <div style="color: #475569;">Centro de Reservas</div>
                            <div style="font-weight: bold; color: #0f172a; margin-top: 4px;">{{ $agentName ?? ($booking->agent ? $booking->agent->name : 'Agente') }}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Teléfono: +1 888-476-0932 || Ext: {{ $agentExt ?? '187' }}</div>
                        </td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>

</body>
</html>
