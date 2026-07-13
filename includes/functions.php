<?php
/**
 * Asgard Store - Funcoes Auxiliares
 * ========================================
 */

require_once __DIR__ . '/../db.php';

// ============================================
// SEGURANCA
// ============================================

// Gerar token CSRF
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function validate_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Campo hidden CSRF
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

// Deletar registro
function db_delete(string $table, string $where, array $params = []): bool {
    $table = preg_replace('/[^a-z0-9_]/i', '', $table);
    $sql = "DELETE FROM `{$table}` WHERE {$where}";
    return db_query($sql, $params)->rowCount() > 0;
}

// Validar CSRF em requisicoes POST
function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME] ?? '')) {
            http_response_code(403);
            die('Token CSRF invalido.');
        }
    }
}

// Hash de senha
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Verificar senha
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// Sanitizar输入
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Gerar slug
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

// ============================================
// AUTENTICACAO
// ============================================

// Fazer login
function login_user(string $email, string $password): ?array {
    $user = db_fetch(
        "SELECT * FROM usuarios WHERE email = ? AND status = 'ativo'",
        [$email]
    );
    
    if ($user && verify_password($password, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_admin'] = $user['admin'];
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        return $user;
    }
    return null;
}

// Fazer logout
function logout_user(): void {
    session_destroy();
    header('Location: ' . SITE_URL . '/auth/login.php');
    exit;
}

// Verificar se esta logado
function is_logged_in(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Verificar se e admin
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_admin']) && $_SESSION['user_admin'] == 1;
}

// Obter usuario atual
function current_user(): ?array {
    if (!is_logged_in()) return null;
    return db_fetch("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);
}

// Redirecionar se nao logado
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

// Redirecionar se nao admin
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: ' . SITE_URL . '/painel/');
        exit;
    }
}

// ============================================
// FORMATACAO
// ============================================

// Formatar moeda
function format_money(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Formatar data
function format_date(string $date, string $format = 'd/m/Y H:i'): string {
    return date($format, strtotime($date));
}

// Tempo relativo
function time_ago(string $datetime): string {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    
    if ($diff->y > 0) return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
    if ($diff->d > 0) return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 'utos' : '');
    return 'agora';
}

// ============================================
// UPLOAD
// ============================================

// Upload de imagem
function upload_image(array $file, string $directory, array $allowed_types = null): ?string {
    if ($allowed_types === null) {
        $allowed_types = ALLOWED_IMAGE_TYPES;
    }
    
    // Verificar erro
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Verificar tamanho
    if ($file['size'] > MAX_FILE_SIZE) {
        return null;
    }
    
    // Verificar tipo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types)) {
        return null;
    }
    
    // Gerar nome unico
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    // Criar diretorio se nao existir
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Mover arquivo
    $destination = $directory . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    
    return null;
}

// ============================================
// NOTIFICACOES
// ============================================

// Criar notificacao
function create_notification(int $user_id, string $title, string $message, string $type = 'info', string $link = ''): bool {
    return db_insert('notificacoes', [
        'usuario_id' => $user_id,
        'titulo' => $title,
        'mensagem' => $message,
        'tipo' => $type,
        'link' => $link,
        'lida' => 0
    ]);
}

// Contar nao lidas
function count_unread_notifications(int $user_id): int {
    return db_count('notificacoes', 'usuario_id = ? AND lida = 0', [$user_id]);
}

// ============================================
// RESPOSTAS JSON
// ============================================

// Resposta JSON de sucesso
function json_success($data = null, string $message = 'Sucesso'): void {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Resposta JSON de erro
function json_error(string $message = 'Erro', int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// ============================================
// PAGINACAO
// ============================================

function paginate(int $total, int $per_page = 12, int $current = 1): array {
    $total_pages = max(1, ceil($total / $per_page));
    $current = max(1, min($current, $total_pages));
    $offset = ($current - 1) * $per_page;
    
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current' => $current,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current > 1,
        'has_next' => $current < $total_pages
    ];
}

function render_pagination(array $pagination, string $base_url): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Anterior
    if ($pagination['has_prev']) {
        $html .= '<a href="' . $base_url . '?page=' . ($pagination['current'] - 1) . '" class="page-btn">&laquo; Anterior</a>';
    }
    
    // Numeros
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        if ($i == $pagination['current']) {
            $html .= '<span class="page-btn active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $base_url . '?page=' . $i . '" class="page-btn">' . $i . '</a>';
        }
    }
    
    // Proximo
    if ($pagination['has_next']) {
        $html .= '<a href="' . $base_url . '?page=' . ($pagination['current'] + 1) . '" class="page-btn">Proximo &raquo;</a>';
    }
    
    $html .= '</div>';
    return $html;
}
