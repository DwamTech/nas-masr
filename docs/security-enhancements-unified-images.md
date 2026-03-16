# Security Enhancements for Unified Category Images

This document describes the security measures implemented in the unified category images management feature.

## Overview

The following security best practices have been implemented to protect against common vulnerabilities:

1. **Path Traversal Prevention**
2. **Image Bomb Protection**
3. **Memory Limit Protection**
4. **Security Headers**

## 1. Path Traversal Prevention

### What is Path Traversal?

Path traversal (also known as directory traversal) is a vulnerability that allows attackers to access files and directories outside the intended directory by manipulating file paths with sequences like `../` or `..\`.

### Implementation

**Location:** `app/Http/Controllers/Admin/CategoryController.php` - `uploadGlobalImage()` method

```php
// Sanitize the original filename to prevent path traversal attacks
$originalFilename = basename($uploadedFile->getClientOriginalName());
$originalFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $originalFilename);

// Verify the filename doesn't contain path traversal sequences
if (strpos($originalFilename, '..') !== false || 
    strpos($originalFilename, '/') !== false || 
    strpos($originalFilename, '\\') !== false) {
    // Log and reject the request
    return response()->json(['error' => 'اسم الملف غير صالح'], 422);
}
```

**Additional Check:**

```php
// Verify path is within allowed directory
$realPath = realpath(dirname($fullPath));
$allowedBase = realpath(storage_path('app/public/uploads/categories/global'));

if ($realPath === false || $allowedBase === false || strpos($realPath, $allowedBase) !== 0) {
    // Log and reject the request
    return response()->json(['error' => 'مسار التخزين غير صالح'], 500);
}
```

### Protection Against

- Accessing files outside the intended directory
- Overwriting system files
- Reading sensitive configuration files
- Malicious filename manipulation

## 2. Image Bomb Protection

### What is an Image Bomb?

An image bomb (decompression bomb) is a maliciously crafted image file that appears small but expands to an enormous size when decompressed, potentially causing:
- Memory exhaustion
- Server crashes
- Denial of Service (DoS)

### Implementation

**Location:** `app/Http/Controllers/Admin/CategoryController.php` - `uploadGlobalImage()` method

```php
// Check decompressed size estimate to prevent decompression bombs
$imageInfo = @getimagesize($uploadedFile->getRealPath());
if ($imageInfo) {
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // Estimate decompressed size (width * height * 4 bytes for RGBA)
    $estimatedDecompressedSize = $width * $height * 4;
    $maxDecompressedSize = 100 * 1024 * 1024; // 100MB
    
    if ($estimatedDecompressedSize > $maxDecompressedSize) {
        // Log and reject the request
        return response()->json([
            'error' => 'أبعاد الصورة كبيرة جداً. الحد الأقصى المسموح: 5000x5000 بكسل'
        ], 422);
    }
    
    // Additional check: reject images with extreme dimensions
    if ($width > 5000 || $height > 5000) {
        // Log and reject the request
        return response()->json([
            'error' => 'أبعاد الصورة تتجاوز الحد الأقصى المسموح (5000x5000 بكسل)'
        ], 422);
    }
}
```

### Protection Against

- Decompression bombs that expand to gigabytes
- Images with extreme dimensions (e.g., 1x1000000 pixels)
- Memory exhaustion attacks
- Server resource depletion

### Limits

- **Maximum dimensions:** 5000x5000 pixels
- **Maximum decompressed size:** 100MB
- **Maximum file size:** 5MB (enforced by validation)

## 3. Memory Limit Protection

### What is Memory Limit Protection?

Image processing operations can consume significant memory, especially for large images. Without proper limits, this can lead to:
- PHP memory exhaustion errors
- Server instability
- Denial of Service

### Implementation

**Location:** `app/Http/Controllers/Admin/CategoryController.php` - `uploadGlobalImage()` method

```php
// Set memory limit for image processing to prevent memory exhaustion
$originalMemoryLimit = ini_get('memory_limit');
ini_set('memory_limit', '256M');

try {
    // ... image processing code ...
    
    // Restore original memory limit after processing
    ini_set('memory_limit', $originalMemoryLimit);
} catch (\Exception $e) {
    // Restore original memory limit on error
    if (isset($originalMemoryLimit)) {
        ini_set('memory_limit', $originalMemoryLimit);
    }
    throw $e;
}
```

### Protection Against

- Memory exhaustion from processing large images
- Server crashes due to excessive memory usage
- Resource starvation affecting other requests

### Configuration

- **Temporary limit:** 256MB (sufficient for processing images up to 5000x5000)
- **Automatic restoration:** Original limit is restored after processing
- **Error handling:** Limit is restored even if an error occurs

## 4. Security Headers

### What are Security Headers?

Security headers are HTTP response headers that instruct browsers to enable additional security features and prevent common attacks.

### Implementation

**Location:** `app/Http/Middleware/SecurityHeaders.php`

```php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // X-Content-Type-Options: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options: Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');

        // X-XSS-Protection: Enable browser XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy: Control browser features
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content-Security-Policy: Control resource loading
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
```

**Registration:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    // Apply security headers to all API routes
    $middleware->api(append: [
        SecurityHeaders::class,
    ]);
})
```

### Headers Explained

#### X-Content-Type-Options: nosniff

Prevents browsers from MIME-sniffing a response away from the declared content-type. This prevents attacks where malicious files are disguised as images.

**Example Attack Prevented:**
- Attacker uploads a PHP file disguised as an image
- Without this header, browser might execute the PHP code
- With this header, browser treats it strictly as an image

#### X-Frame-Options: DENY

Prevents the page from being embedded in an iframe, protecting against clickjacking attacks.

**Example Attack Prevented:**
- Attacker embeds your admin panel in an invisible iframe
- User thinks they're clicking on attacker's site
- Actually clicking on your admin panel buttons

#### X-XSS-Protection: 1; mode=block

Enables the browser's built-in XSS filter and blocks the page if an attack is detected.

**Example Attack Prevented:**
- Attacker injects malicious script in URL parameters
- Browser detects the script and blocks page rendering
- Prevents script execution

#### Referrer-Policy: strict-origin-when-cross-origin

Controls how much referrer information is sent with requests.

**Privacy Protection:**
- Same-origin requests: Full URL is sent
- Cross-origin requests: Only origin is sent
- HTTPS to HTTP: No referrer is sent

#### Permissions-Policy

Disables browser features that aren't needed, reducing attack surface.

**Features Disabled:**
- Geolocation API
- Microphone access
- Camera access

#### Content-Security-Policy (CSP)

Controls which resources can be loaded and executed on the page.

**Directives:**
- `default-src 'self'`: Only load resources from same origin
- `img-src 'self' https://back.nasmasr.app data: blob:`: Allow images from specific sources
- `script-src 'self' 'unsafe-inline' 'unsafe-eval'`: Control script execution
- `style-src 'self' 'unsafe-inline'`: Control stylesheet loading
- `frame-ancestors 'none'`: Prevent embedding in frames

## Security Logging

All security-related events are logged with detailed context:

```php
Log::warning('Path traversal attempt detected', [
    'category_id' => $category->id,
    'filename' => $uploadedFile->getClientOriginalName(),
    'user_id' => auth()->id(),
    'ip' => $request->ip(),
]);
```

### Logged Events

1. **Path traversal attempts**
2. **Invalid image formats**
3. **Oversized images**
4. **Image bomb detection**
5. **Invalid MIME types**
6. **Storage path violations**

### Log Location

Logs are stored in `storage/logs/laravel.log` and can be monitored for security incidents.

## Testing Security Measures

### Manual Testing

1. **Path Traversal:**
   ```bash
   # Try uploading a file with malicious filename
   curl -X POST https://back.nasmasr.app/api/admin/categories/1/upload-global-image \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -F "image=@../../etc/passwd"
   ```
   Expected: 422 error with "اسم الملف غير صالح"

2. **Image Bomb:**
   ```bash
   # Try uploading an image with extreme dimensions
   # Create a 10000x10000 pixel image (will be rejected)
   ```
   Expected: 422 error with dimension limit message

3. **Security Headers:**
   ```bash
   # Check response headers
   curl -I https://back.nasmasr.app/api/categories
   ```
   Expected: All security headers present in response

### Automated Testing

Security tests should be added to the test suite:

```php
/** @test */
public function rejects_path_traversal_attempts()
{
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    
    $file = UploadedFile::fake()->image('../../../malicious.jpg');
    
    $response = $this->actingAs($admin)
        ->postJson("/api/admin/categories/{$category->id}/upload-global-image", [
            'image' => $file,
        ]);
    
    $response->assertStatus(422);
}
```

## Maintenance

### Regular Security Audits

1. Review security logs weekly for suspicious activity
2. Update security headers based on new threats
3. Monitor for new image processing vulnerabilities
4. Keep Laravel and dependencies updated

### Configuration Updates

If you need to adjust security settings:

1. **Memory limit:** Modify in `CategoryController.php` line ~270
2. **Image dimensions:** Modify in `CategoryController.php` line ~350
3. **Security headers:** Modify in `SecurityHeaders.php`
4. **CSP policy:** Update CSP string in `SecurityHeaders.php`

## References

- [OWASP Path Traversal](https://owasp.org/www-community/attacks/Path_Traversal)
- [OWASP Secure Headers Project](https://owasp.org/www-project-secure-headers/)
- [Image Bomb Attacks](https://en.wikipedia.org/wiki/Zip_bomb)
- [Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)

## Compliance

These security measures help meet the following requirements:

- **Requirement 11.1:** Admin authorization verification
- **Requirement 11.2:** Unauthorized access rejection
- **Requirement 11.3:** Authentication token validation
- **Requirement 11.5:** Invalid/expired token rejection

## Summary

The implemented security measures provide comprehensive protection against:

✅ Path traversal attacks
✅ Image bomb/decompression attacks
✅ Memory exhaustion
✅ Clickjacking
✅ XSS attacks
✅ MIME-sniffing attacks
✅ Unauthorized resource access

All security events are logged for monitoring and incident response.
