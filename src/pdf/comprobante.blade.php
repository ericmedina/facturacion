<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title></title>
	<link rel="stylesheet" type="text/css" href="css/comprobante.css">
</head>
<body>
	@for($i = 0; $i < 2; $i++)
	<div id="" name="" class="border-1">	
		@if($comprobante->tipo == 91 || $comprobante->tipo == 100)
		<div class="tipo-comprobante text-center">
			<p style="font-size: 35px;margin-bottom: -7px;margin-top: -3px;">X</p>
		</div>
		@else
		<div class="tipo-comprobante text-center">
			<p style="font-size: 35px;margin-bottom: -7px;margin-top: -3px;">{{$comprobante->tipo_comprobante()}}</p>
			<p style="margin-top: -3px;">Cod. {{str_pad($comprobante->tipo, 2, '0', STR_PAD_LEFT)}}</p>
		</div>
		@endif
		<div class="cabecera">
			<div class="datos-empresa text-center">
				@if(config('facturacion.nombre_fantasia') != "" && config('facturacion.nombre_fantasia') != null)
					<h1 class="text-center" style="font-weight: bold;">{{ config('facturacion.nombre_fantasia') }}</h1>
					<div>
						<p>De: {{config('facturacion.razon_social')}}</p>			
						<p>{{config('facturacion.direccion')}}</p>			
						<p>{{config('facturacion.localidad')}}</p>			
						<p>Telefono: {{config('facturacion.telefono')}}</p>
						<p class="underline">{{config('facturacion.responsabilidad_iva')}}</p>			
					</div>
				@else
					<h1 class="text-center" style="font-weight: bold;">{{config('facturacion.razon_social')}}</h1>
					<div>
						<p>{{config('facturacion.direccion')}}</p>		
						<p>{{config('facturacion.localidad')}}</p>			
						<p>Telefono: {{config('facturacion.telefono')}}</p>
						<p class="underline">{{config('facturacion.responsabilidad_iva')}}</p>					
					</div>
				@endif
			</div>
			<div class="datos-comprobante text-center">
				<h1 class="text-center"  style="font-weight: bold;">{{$comprobante->titulo_tipo()}}</h1>
				<div>
					<p>N° {{str_pad(config('facturacion.punto_venta'), 4, '0', STR_PAD_LEFT)}} - {{str_pad($comprobante->numero,8,'0',STR_PAD_LEFT)}}</p>
					<p>Fecha: {{$comprobante->fecha}}</p>
					<p>CUIT: {{config('facturacion.cuit')}}</p>
					<p>Inicio actividades: {{config('facturacion.inicio_actividades')}}</p>						
					<h4><strong>@if($i == 0)ORIGINAL @else DUPLICADO @endif</strong></h4>
				</div>
			</div>
		</div>
		<div class="datos-cliente ">
			<table style="border:none;width: 100%;font-size: 12px;margin-top: 10px;">
				<tr style="height: 50px;">
					<th class="label-cliente"><label>Cliente: </label></th>
					<td class="valor-cliente">{{$cliente->nombre ?? ""}}</td>
					<th class="label-cliente">CUIT: </th>
					<td class="valor-cliente">{{$cliente->num_doc ?? ""}}</td>
				</tr>
				<tr style="margin-top: 15px;">
					<th class="label-cliente"><label>Domicilio: </label></th>
					<td class="valor-cliente">{{$cliente->domicilio ?? ""}}</td>
					<th class="label-cliente">Localidad: </th>
					<td class="valor-cliente">{{$cliente->localidad ?? ""}}</td>
				</tr>
				<tr style="margin-top: 15px;">
					<th class="label-cliente"><label>Condición IVA: </label></th>
					<td class="valor-cliente">{{$cliente->condicion_iva ?? ""}}</td>
					<th class="label-cliente">Cond. venta: </th>
					<td class="valor-cliente">{{$cliente->condicion_venta ?? ""}}</td>
				</tr>
			</table>
		</div>
	</div>
	<div class="tabla-detalle">
		<table  style="width: 100%;">
			<thead>
				<tr>
					<th>Cantidad</th>
					<th>Descripción</th>
					<th>Importe</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($detalles as $detalle)
					<tr>
						<td>{{$detalle->cantidad}}</td>
						<td>{{$detalle->descripcion}}</td>
						<td>${{$detalle->importe}}</td>
						<td>${{round($detalle->importe * $detalle->cantidad,2)}}</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	{{-- <div class="observaciones">
		@if($Comprobante->txtObservaciones != '')
			<p><strong>OBSERVACIONES:</strong> {{$Comprobante->txtObservaciones}}</p>
		@endif
	</div> --}}
	<div class="totales">
		@if($comprobante->tipo == 1 || $comprobante->tipo == "3")
			<label style="width: 85%;float: left;" class="text-right">Subtotal:</label><p class="text-right">${{$comprobante->importe_neto}}</p>
			<div style="clear:both;"></div>
			<label style="width: 85%;float: left;" class="text-right">Importe iva:</label><p class="text-right">${{$comprobante->importe_iva}}</p>
			<div style="clear:both;"></div>
			<label style="width: 85%;float: left;" class="text-right">Importe Total:</label><p class="text-right">${{$comprobante->importe_total}}</p>
		@else
			<label style="width: 85%;float: left;" class="text-right">Subtotal:</label><p class="text-right">${{$comprobante->importe_total}}</p>
			<div style="clear:both;"></div>
			<label style="width: 85%;float: left;" class="text-right">Importe iva:</label><p class="text-right">$0</p>
			<div style="clear:both;"></div>
			<label style="width: 85%;float: left;" class="text-right">Importe Total:</label><p class="text-right">${{$comprobante->importe_total}}</p>
		@endif
	</div>
	@if($comprobante->tipo == 91 || $comprobante->tipo == 100)
		<div style="font-size: 10px; padding-top: 5px;" >
				<h1 class="text-center">COMPROBANTE SIN VALIDEZ FISCAL</h1>
			<div></div>
		</div>
	@else
	<div style="font-size: 10px; padding-top: 5px;" >
		<p style="margin-bottom: 10px;"><strong>CAE:</strong> {{$comprobante->cae}} <strong>VENCIMIENTO:</strong> {{date('d/m/Y', strtotime($comprobante->vencimiento_cae))}}</p>
		{!!$barcode!!}
		<p style="width: 300px;text-align: center;font-size: 11px;">{{$codigo}}</p>
	</div>
	@endif
	<div style="position:absolute;bottom:3px;font-size:10px;width: 100%" class="text-center">
		<p>www.pampadev.com.ar - Software de gestión y facturación electrónica - administracion@pampadev.com.ar</p>
	</div>
	@if($i == 0)<div class="page-break"></div>@endif
	@endfor
</body>
</html>