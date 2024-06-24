<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IndexController extends Controller {
    //

    public function index() {
        $tokens = Storage::disk('local')->get('table.json');
        $tokens = json_decode($tokens, true);
        $lexemasPerToken = [];
        foreach ($tokens as $index => $token) {
            $lexemasPerToken[$token['token']] = [
                'total' => count($token['lexemas']),
                'new' => 0
            ];
        }

        return view('home')->with('tokens', $tokens)->with('lexemasPerToken', $lexemasPerToken);
    }


    public function uploadFile(Request $request) {
        $request->validate([
            'file' => 'required|file|mimes:txt|max:2048',
        ]);

        if ($request->file('file')) {
            $file = $request->file('file');
            $uniqueFileName = uniqid() . '_' . $file->getClientOriginalName();

            $path = $file->storeAs('uploads', $uniqueFileName, 'public');
            $content = Storage::disk('public')->get($path);
            $files = Storage::disk('public')->files('uploads');
            $fileCount = count($files);

            $allLexemas = [];
            $words = preg_split('/[\s,.\r\n]+/', $content);

            $json = Storage::disk('local')->get('table.json');
            $tokens = json_decode($json, true);
            $tokenTypes = array_column($tokens, 'token');
            $this->initOutputFile(true);
            $output = Storage::disk('local')->get('output.json');
            $actualJson = json_decode($output, true);

            foreach ($tokens as $index => $token) {
                $tokens[$index]['new'] = 0;
            }

            foreach ($tokens as $token) {
                foreach ($token['lexemas'] as $hash => $lexema) {
                    $allLexemas[$hash] = $lexema;
                }
            }



            $pendingLexemas = [];
            $assignedLexemas = 0;
            foreach ($words as $key => $word) {
                $word = trim($word);
                if ($word !== '') {
                    $hash = md5($word);


                    if (is_numeric($word) || preg_match('/^[^\w\sáéíóúÁÉÍÓÚüÜñÑ]+$/', $word)) {
                        $tokenOtros = array_search('OTROS', array_column($tokens, 'token'));
                        if (!isset($tokens[$tokenOtros]['OTROS']['lexemas'][$hash])) {
                            $tokens[$tokenOtros]['OTROS']['lexemas'][$hash] = $word;
                            $tokens[$tokenOtros]['OTROS']['posiciones'][$hash] = [];
                        }
                        $tokens[$tokenOtros]['OTROS']['posiciones'][$hash][] = 'TXT#' . $fileCount . '-' . ($key + 1);
                        if (!isset($actualJson[$tokenOtros]['OTROS']['lexemas'][$hash])) {
                            $actualJson[$tokenOtros]['OTROS']['lexemas'][$hash] = $word;
                            $actualJson[$tokenOtros]['OTROS']['posiciones'][$hash] = [];
                        }
                        $actualJson[$tokenOtros]['OTROS']['posiciones'][$hash][] = 'TXT#' . $fileCount . '-' . ($key + 1);
                        $tokens[$tokenOtros]['new'] +=1; 

                        $assignedLexemas++;
                        continue;
                    }

                    if (!isset($allLexemas[$hash])) {
                        if (!isset($pendingLexemas[$hash])) {
                            $pendingLexemas[$hash] = [
                                'lexema' => $word,
                                'posiciones' => []
                            ];
                        }
                        $pendingLexemas[$hash]['posiciones'][] = 'TXT#' . $fileCount  . '-' . ($key + 1);
                    } else {
                        foreach ($tokens as $index => $token) {
                            if (isset($token['lexemas'][$hash])) {
                                $tokens[$index]['posiciones'][$hash][] = 'TXT#' . $fileCount  . '-' . ($key + 1);
                                $actualJson[$index]['posiciones'][$hash][] = 'TXT#' . $fileCount  . '-' . ($key + 1);
                                $actualJson[$index]['lexemas'][$hash] = $word;
                                $assignedLexemas++;
                                $tokens[$index]['new'] += 1;
                            }
                        }
                    }
                }
            }

            $totalLexemas = count($words);
            $processedLexemas = $totalLexemas - count($pendingLexemas);
            $processedPercentage = ($processedLexemas / $totalLexemas) * 100;
            $unprocessedPercentage = 100 - $processedPercentage;

            $lexemasPerToken = [];
            foreach ($tokens as $index => $token) {
                $lexemasPerToken[$token['token']] = [
                    'total' => array_reduce($token['posiciones'], function ($carry, $item) {
                        return $carry + count($item);
                    }, 0),
                    'new' => $token['new']
                ];
            }

            Storage::disk('local')->put('table.json', json_encode($tokens, JSON_PRETTY_PRINT));
            Storage::disk('local')->put('output.json', json_encode($actualJson, JSON_PRETTY_PRINT));
            $pendingLexemas = array_values($pendingLexemas);
            $downloadUrl = empty($pendingLexemas) ? route('downloadOutput') : null;

            return view('home')->with('success', 'Archivo subido y procesado exitosamente.')
                ->with('file_content', $content)
                ->with('pendingLexemas', $pendingLexemas)
                ->with('tokenTypes', $tokenTypes)
                ->with('totalLexemas', $totalLexemas)
                ->with('processedPercentage', $processedPercentage)
                ->with('unprocessedPercentage', $unprocessedPercentage)
                ->with('lexemasPerToken', $lexemasPerToken)
                ->with('assignedLexemas', $assignedLexemas)
                ->with('downloadUrl', $downloadUrl);
        }

        return back()->withErrors(['file' => 'Hubo un problema al subir el archivo.']);
    }


    public function assignTokens(Request $request) {


        $json = Storage::disk('local')->get('table.json');
        $tokens = json_decode($json, true);
        $tokenTypes = array_column($tokens, 'token');
        $this->initOutputFile(true);
        $output = Storage::disk('local')->get('output.json');
        $actualJson = json_decode($output, true);
        $data = $request->input('lexema');
        $assignedTokens = $request->input('token');
        foreach ($data as $index => $row) {
            $row = json_decode($row, true);
            $hash = md5($row['lexema']);
            foreach ($tokens as $t => $token) { // COMPLEJIDAD CONSTANTE
                if ($token['token'] === $assignedTokens[$index]) {
                    $tokens[$t]['lexemas'][$hash] = $row['lexema'];
                    $tokens[$t]['posiciones'][$hash] = $row['posiciones'];
                    $actualJson[$t]['lexemas'][$hash] = $row['lexema'];
                    $actualJson[$t]['posiciones'][$hash] = $row['posiciones'];
                }
            }
        }

        $lexemasPerToken = [];
            foreach ($tokens as $index => $token) {
                $lexemasPerToken[$token['token']] = [
                    'total' => array_reduce($token['posiciones'], function ($carry, $item) {
                        return $carry + count($item);
                    }, 0),
                    'new' => array_reduce($actualJson[$index]['posiciones'], function ($carry, $item) {
                        return $carry + count($item);
                    }, 0),
                ];
            }

        Storage::disk('local')->put('table.json', json_encode($tokens, JSON_PRETTY_PRINT));
        Storage::disk('local')->put('output.json', json_encode($actualJson, JSON_PRETTY_PRINT));
        $downloadUrl = route('downloadOutput');

        return view('home')->with('success', 'Tokens asignados exitosamente.')
        ->with('downloadUrl', $downloadUrl)
        ->with('lexemasPerToken', $lexemasPerToken);
    }

    public function downloadOutput() {
        $output = Storage::disk('local')->get('output.json');
        $output = json_decode($output, true);
        foreach ($output as &$tokenGroup) {
            if (isset($tokenGroup['lexemas'])) {
                $tokenGroup['lexemas'] = array_values($tokenGroup['lexemas']);
            }
            if (isset($tokenGroup['posiciones'])) {
                $tokenGroup['posiciones'] = array_merge(...array_values($tokenGroup['posiciones']));
            }
        }
    
        $newOutput = json_encode($output, JSON_PRETTY_PRINT);
        Storage::disk('local')->put('output.json', $newOutput);
        
        return response()->download(storage_path('app/output.json'));
    }

    public function initOutputFile($isOutput = false) {
        $tokens = [
            'ARTICULO',
            'SUSTANTIVO',
            'VERBO',
            'ADJETIVO',
            'ADVERBIO',
            'OTROS',
            'ERROR_LX'
        ];
        $output = [];
        foreach ($tokens as $token) {
            $output[] = [
                'token' => $token,
                'lexemas' => [],
                'posiciones' => []
            ];
        }
        if ($isOutput) {
            Storage::disk('local')->put('output.json', json_encode($output, JSON_PRETTY_PRINT));
        }else {
            Storage::disk('local')->put('table.json', json_encode($output, JSON_PRETTY_PRINT));
            return redirect()->route('home');
        }
    }
}
