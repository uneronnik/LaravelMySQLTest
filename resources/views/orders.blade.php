<!DOCTYPE html>
<html>
<body>
    <h1>Заказы</h1>

{{-- Если $orders — массив объектов/массивов --}}
@if($orders)
    @foreach($orders as $order)
        <pre>{{ print_r($order, true) }}</pre>
    @endforeach
@else
    <p>Нет данных</p>
    @endif

    </body>
    </html>
