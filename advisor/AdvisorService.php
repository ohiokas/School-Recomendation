<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../recommendation/Scoring.php';
require_once __DIR__ . '/../ml/Predictor.php';

final class AdvisorService
{
    private const MODEL = 'gpt-4o-mini';
    private const INTERNAL_SOURCE = 'Data sistem SMAKITA: profil, skor minat, hasil Gaussian Naive Bayes, dan data sekolah.';

    public static function answer(string $question, array $user): array
    {
        $question = trim($question);
        if ($question === '') throw new InvalidArgumentException('Pertanyaan tidak boleh kosong.');
        if (mb_strlen($question) > 2000) throw new InvalidArgumentException('Pertanyaan terlalu panjang.');

        $apiKey = trim((string)getenv('OPENAI_API_KEY'));
        if ($apiKey === '') {
            return self::fallbackAnswer($question, $user);
        }

        $system = self::systemInstruction($user);
        $contents = [['role' => 'user', 'content' => $question]];
        $sources = [];
        $toolCalls = 0;

        for ($turn = 0; $turn < 4; $turn++) {
            $response = self::requestOpenAI($apiKey, $system, $contents, self::toolDeclarations());
            $functionParts = [];
            foreach (($response['output'] ?? []) as $part) {
                if (($part['type'] ?? '') !== 'function_call') continue;
                if (++$toolCalls > 6) break 2;
                $name = (string)($part['name'] ?? '');
                $args = json_decode((string)($part['arguments'] ?? '{}'), true);
                $args = is_array($args) ? $args : [];
                $result = self::runTool($name, $args, $user);
                if ($name === 'search_web') {
                    foreach (($result['sources'] ?? []) as $source) $sources[] = $source;
                }
                $functionParts[] = ['type' => 'function_call_output', 'call_id' => (string)($part['call_id'] ?? ''), 'output' => json_encode($result, JSON_UNESCAPED_UNICODE)];
            }
            if (!$functionParts) {
                $text = self::responseText($response);
                if ($text === '') throw new RuntimeException('OpenAI tidak mengembalikan jawaban.');
                return ['answer' => $text, 'sources' => self::uniqueSources(array_merge($sources, self::responseSources($response))), 'provider' => 'openai'];
            }
            $contents = array_merge($contents, $response['output'] ?? [], $functionParts);
        }

        throw new RuntimeException('Permintaan advisor melewati batas pemanggilan tool.');
    }

    private static function systemInstruction(array $user): string
    {
        $role = (($user['role'] ?? '') === 'admin') ? 'admin' : 'student';
        return "Kamu adalah AI School Advisor untuk sistem rekomendasi SMA/SMK Negeri Jakarta.\n"
            . "Jawab dalam bahasa Indonesia yang ramah, jelas, dan ringkas untuk siswa SMP, orang tua, atau admin.\n"
            . "Peran pengguna saat ini: {$role}. Jangan membocorkan data siswa lain.\n"
            . "Untuk profil siswa, nilai, minat, sekolah, skor, ranking, dan alasan rekomendasi, WAJIB gunakan tool internal.\n"
            . "Kamu juga boleh menjawab pertanyaan umum di luar dataset, termasuk pelajaran sekolah, teknologi, karier, definisi, cara kerja sesuatu, menulis, brainstorming, dan percakapan sehari-hari. Untuk pertanyaan umum yang tidak membutuhkan informasi terkini, jawab langsung dengan pengetahuanmu dan jangan memanggil tool internal.\n"
            . "Gaussian Naive Bayes adalah mesin rekomendasi utama. Jangan menghitung ulang, mengubah skor, ranking, atau probabilitasnya. Jangan menyebut skor rekomendasi sebagai peluang diterima kecuali data memang menyatakannya demikian.\n"
            . "Untuk informasi terbaru, aturan penerimaan, berita, harga, jadwal, tokoh yang sedang menjabat, prospek jurusan terkini, atau fakta di luar data internal yang perlu diverifikasi, gunakan web search. Untuk pertanyaan umum yang stabil, tidak wajib menggunakan web. Prioritaskan sumber resmi pemerintah, Dinas Pendidikan, dan situs sekolah.\n"
            . "Pisahkan dengan jelas informasi [Data sistem] dan [Sumber eksternal]. Jangan mengarang. Jika informasi tidak ditemukan, katakan terus terang.\n"
            . "Jika pertanyaan meminta rekomendasi sekolah, jelaskan hasil sistem yang ada, jangan membuat rekomendasi baru berdasarkan opini pribadi.\n"
            . "Data internal yang dipakai berasal dari database/SMKSMA404.csv dan model terlatih Dataset Project 404.xlsx. Jangan mengklaim data eksternal sebagai data sistem.";
    }

    private static function toolDeclarations(): array
    {
        $string = ['type' => 'string'];
        return [
            ['type' => 'function', 'name' => 'get_student_profile', 'description' => 'Mengambil profil akademik, minat, domisili, dan jalur siswa yang diizinkan.', 'parameters' => ['type' => 'object', 'properties' => ['student_id' => ['type' => 'integer']], 'required' => [], 'additionalProperties' => false]],
            ['type' => 'function', 'name' => 'get_gnb_recommendations', 'description' => 'Mengambil hasil Gaussian Naive Bayes terakhir dan rekomendasi sekolah yang tersimpan.', 'parameters' => ['type' => 'object', 'properties' => ['student_id' => ['type' => 'integer']], 'required' => [], 'additionalProperties' => false]],
            ['type' => 'function', 'name' => 'get_school_data', 'description' => 'Mengambil data sekolah internal berdasarkan nama atau NPSN.', 'parameters' => ['type' => 'object', 'properties' => ['query' => $string], 'required' => ['query'], 'additionalProperties' => false]],
            ['type' => 'function', 'name' => 'get_school_comparison', 'description' => 'Membandingkan maksimal tiga sekolah dari data internal.', 'parameters' => ['type' => 'object', 'properties' => ['schools' => ['type' => 'array', 'items' => $string]], 'required' => ['schools'], 'additionalProperties' => false]],
            ['type' => 'web_search_preview'],
        ];
    }

    private static function runTool(string $name, array $args, array $user): array
    {
        return match ($name) {
            'get_student_profile' => self::studentProfile($args, $user),
            'get_gnb_recommendations' => self::gnbRecommendations($args, $user),
            'get_school_data' => self::schoolData((string)($args['query'] ?? '')),
            'get_school_comparison' => self::schoolComparison($args['schools'] ?? []),
            'search_web' => self::searchWeb((string)($args['query'] ?? '')),
            default => ['error' => 'Tool tidak dikenal.'],
        };
    }

    private static function authorizedStudentId(array $args, array $user): ?int
    {
        if (($user['role'] ?? '') === 'admin' && !empty($args['student_id'])) return (int)$args['student_id'];
        $query = db()->prepare('SELECT id FROM students WHERE user_id=? LIMIT 1');
        $query->execute([(int)$user['id']]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    private static function studentProfile(array $args, array $user): array
    {
        $studentId = self::authorizedStudentId($args, $user);
        if (!$studentId) return ['error' => 'Profil siswa belum tersedia.'];
        $query = db()->prepare('SELECT s.id,s.education_preference,s.domicile,s.admission_path,u.full_name,a.academic_average,a.science,a.social_studies,a.indonesian,a.english,a.mathematics,a.civics FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN academic_scores a ON a.student_id=s.id WHERE s.id=? LIMIT 1');
        $query->execute([$studentId]);
        $profile = $query->fetch();
        if (!$profile) return ['error' => 'Profil tidak ditemukan.'];
        $scores = [];
        foreach (['science','social_studies','indonesian','english','mathematics','civics'] as $subject) $scores[$subject] = (float)($profile[$subject] ?? 0);
        return ['source' => 'internal', 'profile' => ['name' => $profile['full_name'], 'education_preference' => $profile['education_preference'], 'domicile' => $profile['domicile'], 'admission_path' => $profile['admission_path'], 'academic_average' => (float)($profile['academic_average'] ?? 0), 'subject_scores' => $scores], 'interest_scores' => self::interestScores($studentId)];
    }

    private static function interestScores(int $studentId): array
    {
        $query = db()->prepare('SELECT indicator,score FROM interest_scores WHERE student_id=? ORDER BY score DESC');
        $query->execute([$studentId]);
        $scores = [];
        foreach ($query->fetchAll() as $row) $scores[(string)$row['indicator']] = (float)$row['score'];
        return $scores;
    }

    private static function gnbRecommendations(array $args, array $user): array
    {
        $studentId = self::authorizedStudentId($args, $user);
        if (!$studentId) return ['error' => 'Hasil rekomendasi belum tersedia.'];
        $query = db()->prepare('SELECT predicted_interest,confidence,student_score,created_at FROM recommendations WHERE student_id=? ORDER BY created_at DESC LIMIT 1');
        $query->execute([$studentId]);
        $recommendation = $query->fetch();
        if (!$recommendation) return ['source' => 'internal', 'message' => 'Belum ada hasil rekomendasi tersimpan.'];
        $recommendation['confidence'] = (float)$recommendation['confidence'];
        $recommendation['student_score'] = (float)$recommendation['student_score'];
        $recommendation['note'] = 'Confidence adalah keluaran model; bukan peluang diterima sekolah.';
        return ['source' => 'internal', 'model' => 'Gaussian Naive Bayes', 'recommendation' => $recommendation, 'interest_scores' => self::interestScores($studentId)];
    }

    private static function schoolData(string $search): array
    {
        $search = trim($search);
        if ($search === '') return ['error' => 'Nama sekolah atau NPSN wajib diisi.'];
        $query = db()->prepare('SELECT sd.*,GROUP_CONCAT(sc.competency SEPARATOR "||") competencies_text FROM school_data sd LEFT JOIN school_competencies sc ON sc.school_id=sd.id WHERE sd.is_active=1 AND (sd.name LIKE ? OR sd.npsn LIKE ? OR sd.address LIKE ?) GROUP BY sd.id ORDER BY sd.name LIMIT 10');
        $like = '%' . $search . '%'; $query->execute([$like, $like, $like]);
        return ['source' => 'internal', 'schools' => array_map([self::class, 'schoolDto'], $query->fetchAll())];
    }

    private static function schoolComparison(mixed $schools): array
    {
        if (!is_array($schools)) return ['error' => 'Daftar sekolah tidak valid.'];
        $result = [];
        foreach (array_slice($schools, 0, 3) as $school) {
            $items = self::schoolData((string)$school)['schools'] ?? [];
            if ($items) $result[] = $items[0];
        }
        return ['source' => 'internal', 'schools' => $result];
    }

    private static function schoolDto(array $row): array
    {
        return ['npsn' => $row['npsn'], 'name' => $row['name'], 'address' => $row['address'], 'type' => $row['school_type'], 'domicile' => $row['domicile'], 'accreditation' => $row['accreditation'], 'average_score' => (float)$row['average_score'], 'capacities' => ['general' => $row['capacity'], 'prestasi_akademik' => $row['capacity_prestasi_akademik'], 'prestasi_nonakademik' => $row['capacity_prestasi_nonakademik'], 'afirmasi' => $row['capacity_afirmasi'], 'zonasi' => $row['capacity_zonasi'], 'tahap_kedua' => $row['capacity_tahap_kedua']], 'competencies' => array_values(array_filter(explode('||', (string)$row['competencies_text'])))];
    }

    private static function searchWeb(string $query): array
    {
        if (trim($query) === '') return ['error' => 'Query pencarian kosong.'];
        $apiKey = trim((string)getenv('OPENAI_API_KEY'));
        if ($apiKey === '') return ['error' => 'Pencarian web belum dikonfigurasi.'];
        try {
            $response = self::requestOpenAI($apiKey, 'Gunakan web search untuk menjawab query. Prioritaskan sumber resmi Indonesia dan kembalikan fakta singkat.', [['role' => 'user', 'content' => $query]], [['type' => 'web_search_preview']]);
            return ['source' => 'external', 'answer' => self::responseText($response), 'sources' => self::responseSources($response)];
        } catch (Throwable $exception) {
            return ['source' => 'external', 'error' => 'Pencarian web gagal sementara.'];
        }
    }

    private static function requestOpenAI(string $apiKey, string $system, array $contents, array $tools): array
    {
        $url = 'https://api.openai.com/v1/responses';
        $payload = ['model' => self::MODEL, 'instructions' => $system, 'input' => $contents, 'tools' => $tools, 'temperature' => 0.2, 'max_output_tokens' => 1200];
        $curl = curl_init($url);
        $options = [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey], CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE), CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 35];
        $caBundle = 'C:/xampp/php/extras/ssl/cacert.pem';
        if (is_readable($caBundle)) $options[CURLOPT_CAINFO] = $caBundle;
        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl); $curlError = curl_error($curl); $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        if ($raw === false) { error_log('AI School Advisor OpenAI transport error: ' . $curlError); throw new RuntimeException('OpenAI connection failed.'); }
        $decoded = json_decode((string)$raw, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded)) { error_log('AI School Advisor OpenAI HTTP status: ' . $status . ' response: ' . substr((string)$raw, 0, 500)); throw new RuntimeException($status === 401 || $status === 403 ? 'OpenAI API key ditolak.' : ($status === 429 ? 'Quota OpenAI habis.' : 'OpenAI request failed.')); }
        return $decoded;
    }

    private static function responseText(array $response): string
    {
        $text = (string)($response['output_text'] ?? '');
        if ($text !== '') return trim($text);
        foreach (($response['output'] ?? []) as $part) foreach (($part['content'] ?? []) as $content) $text .= (string)($content['text'] ?? '');
        return trim($text);
    }

    private static function responseSources(array $response): array
    {
        $sources = [];
        foreach (($response['output'] ?? []) as $item) foreach (($item['content'] ?? []) as $content) foreach (($content['annotations'] ?? []) as $annotation) {
            $url = (string)($annotation['url'] ?? '');
            if ($url !== '') $sources[] = ['type' => 'external', 'title' => (string)($annotation['title'] ?? $url), 'url' => $url];
        }
        return self::uniqueSources($sources);
    }

    private static function uniqueSources(array $sources): array
    {
        $seen = []; $result = [];
        foreach ($sources as $source) { $url = (string)($source['url'] ?? ''); if ($url !== '' && !isset($seen[$url])) { $seen[$url] = true; $result[] = $source; } }
        return $result;
    }

    private static function fallbackAnswer(string $question, array $user): array
    {
        return ['answer' => 'AI School Advisor belum aktif karena OPENAI_API_KEY belum dikonfigurasi di environment server. Data rekomendasi tetap tersedia di halaman Rekomendasi.', 'sources' => [], 'provider' => 'configuration'];
    }
}
