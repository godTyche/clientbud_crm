@if ($multiple)
    <div {{ $attributes }}></div>
    <script>
        var data = {
            labels: [
                @foreach ($chartData['labels'] as $label)
                    "{{ $label }}",
                @endforeach
            ],
            datasets: [
                    @foreach ($chartData['values'] as $key => $value)
                {
                    name: "{{ $chartData['name'][$key] }}",
                    values: [
                        @foreach ($value as $key => $val)
                            {{ $val }},
                        @endforeach
                    ],
                    chartType: 'bar'
                },
                @endforeach
            ]
        };
    </script>

    <script>
        var chart = new frappe.Chart("#{{ $attributes['id'] }}", { // or a DOM element,
            data: data,
            type: 'bar', // or 'bar', 'line', 'scatter', 'pie', 'percentage'
            height: {{ $attributes['height'] }},
            barOptions: {
                stacked: false,
                spaceRatio: {{$spaceRatio}}
            },
            valuesOverPoints: 0,
            axisOptions: {
                yAxisMode: 'tick',
                xAxisMode: 'tick',
                xIsSeries: 0
            },
            colors: [
                @foreach ($chartData['colors'] as $color)
                    "{{ $color }}",
                @endforeach
            ]
        });
    </script>
@else
    @php
        $valuesTotal = array_sum($chartData['values']);
    @endphp
    @if ($valuesTotal >= 1)
        <div {{ $attributes }}></div>
        <script>
            var data = {
                labels: [
                    @foreach ($chartData['labels'] as $label)
                        "{{ $label }}",
                    @endforeach
                ],
                datasets: [{
                    name: "{{ $chartData['name'] }}",
                    values: [
                        @foreach ($chartData['values'] as $value)
                            {{ $value }},
                        @endforeach
                    ],
                    chartType: 'bar'
                }]
            };
        </script>

        <script>
            var chart = new frappe.Chart("#{{ $attributes['id'] }}", { // or a DOM element,
                data: data,
                type: 'bar', // or 'bar', 'line', 'scatter', 'pie', 'percentage'
                height: {{ $attributes['height'] }},
                barOptions: {
                    stacked: false,
                    spaceRatio: {{ $spaceRatio }}
                },
                valuesOverPoints: 0,
                axisOptions: {
                    yAxisMode: 'tick',
                    xAxisMode: 'tick',
                    xIsSeries: 0
                },
                colors: [
                    @foreach ($chartData['colors'] as $color)
                        "{{ $color }}",
                    @endforeach
                ]
            });
        </script>
    @else
        <div class="align-items-center d-flex flex-column text-lightest p-20"
             style="height: {{ $attributes['height'] }}px">
            <i class="side-icon bi bi-bar-chart"></i>

            <div class="f-15 mt-4">
                - @lang('messages.notEnoughData') -
            </div>
        </div>
    @endif
@endif
