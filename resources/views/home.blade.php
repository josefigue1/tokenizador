@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Subir Archivo</h3>
    </div>
    <div class="card-body">
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
           
        </div>
        @endif

        <div class="d-flex justify-content-end">
    @if (isset($downloadUrl))
        <a href="{{ $downloadUrl }}" class="btn btn-primary mr-2 mb-3">Descargar resultado</a>
    @endif
    <form action="{{ route('reset') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary mb-3">Resetear tabla</button>
    </form>
</div>




    

     

        <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <input class="form-control" type="file" id="file" name="file">
            </div>
            <button type="submit" class="btn btn-primary">Subir</button>
        </form>

        @if (isset($pendingLexemas) && count($pendingLexemas) > 0)
        <div class="alert alert-info">
            <h5>Lexemas Pendientes:</h5>
            <form action="{{ route('assignTokens') }}" method="POST">
                @csrf
                <input type="hidden" name="lexemasPerToken" value="{{ json_encode($lexemasPerToken) }}">

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Lexema</th>
                            <th>Posiciones</th>
                            <th>Asignar Token</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingLexemas as $index => $pendingLexema)
                        <tr>
                            <td>{{ $pendingLexema['lexema'] }}</td>
                            <td>{{ implode(', ', $pendingLexema['posiciones']) }}</td>
                            <td>
                                <select id="token-{{ $index }}" name="token[{{ $index }}]" class="form-control" onchange="updateProcessedLexemas({{ json_encode($pendingLexema) }})">
                                    @foreach ($tokenTypes as $tokenType)
                                    <option value="{{ $tokenType }}">{{ $tokenType }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <input type="hidden" name="lexema[{{ $index }}]" value="{{ json_encode($pendingLexema) }}">
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Asignar Tokens</button>
            </form>
        </div>
        @endif

        <div class="alert alert-info mt-3">
            <h5>Información del proceso:</h5>
            <p>Lexemas procesados: <span id="processedPercentage">{{ $processedPercentage ?? 0 }}</span>%</p>
            <p>Lexemas no procesados: <span id="unprocessedPercentage">{{ $unprocessedPercentage ?? 0 }}</span>%</p>
        </div>

        @if (isset($lexemasPerToken))
        <div class="alert alert-info mt-3">
            <h5>Cantidad de Lexemas por Token:</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Total Lexemas</th>
                        <th>Nuevos Lexemas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lexemasPerToken as $token => $counts)
                    <tr>
                        <td>{{ $token }}</td>
                        <td>{{ $counts['total'] }}</td>
                        <td>{{ $counts['new'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<script>

    var lexemasCalculados = [];
    var assignedLexemas = {{ $assignedLexemas ?? 0 }};
    function updateProcessedLexemas(lexema) {
        var totalLexemas = {{ $totalLexemas ?? 0 }};
        var cambiados = 0
        if (!lexemasCalculados.includes(lexema.lexema)) {
            cambiados = lexema.posiciones.length;
            lexemasCalculados.push(lexema.lexema);
            assignedLexemas += cambiados;
            var processedPercentage = (assignedLexemas / totalLexemas) * 100;
            var unprocessedPercentage = 100 - processedPercentage;

            document.getElementById('processedPercentage').innerText = processedPercentage.toFixed(2);
            document.getElementById('unprocessedPercentage').innerText = unprocessedPercentage.toFixed(2);
        }

      
    }
</script>
@endsection