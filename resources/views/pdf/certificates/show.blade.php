<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $payload = $certificate->certificate_data ?? [];
        $layout = $payload['layout'] ?? [];
        $elements = $payload['elements'] ?? [];
        $backgroundColor = $layout['background_color'] ?? '#ffffff';
        $backgroundImage = $layout['background_image'] ?? null;
        $orientation = strtolower($layout['orientation'] ?? 'landscape');
        $pageWidthMm = $orientation === 'portrait' ? 210 : 297;
        $pageHeightMm = $orientation === 'portrait' ? 297 : 210;
        $canvasWidth = max(1, (int) ($layout['width'] ?? 1684));
        $canvasHeight = max(1, (int) ($layout['height'] ?? 1191));
        $defaultFont = $layout['default_font_family'] ?? 'Inter, "Helvetica Neue", Helvetica, Arial, sans-serif';

        $mmToPx = 96 / 25.4;
        $pageWidthPx = $pageWidthMm * $mmToPx;
        $pageHeightPx = $pageHeightMm * $mmToPx;
        $scaleX = $canvasWidth > 0 ? $pageWidthPx / $canvasWidth : 1;
        $scaleY = $canvasHeight > 0 ? $pageHeightPx / $canvasHeight : 1;

        $formatFloat = static function (float $value): string {
            $formatted = number_format($value, 6, '.', '');

            return $formatted === '0.000000'
                ? '0'
                : rtrim(rtrim($formatted, '0'), '.');
        };

        $resolveAssetUrl = static function (?string $value) {
            if ($value === null || $value === '') {
                return null;
            }

            if (str_contains($value, '://') || str_starts_with($value, 'data:')) {
                return $value;
            }

            return asset(ltrim($value, '/'));
        };

        $backgroundImageUrl = $resolveAssetUrl($backgroundImage);
    @endphp
    <title>Certificate • {{ $certificate->recipient_name }}</title>
    <style>
        @page {
            size: {{ $pageWidthMm }}mm {{ $pageHeightMm }}mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            padding: 0;
            margin: 0;
            width: 100%;
            height: 100%;
            background-color: #111827;
            font-family: {{ $defaultFont }};
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page {
            position: relative;
            width: {{ $pageWidthMm }}mm;
            height: {{ $pageHeightMm }}mm;
            overflow: hidden;
            background-color: {{ $backgroundColor }};
            @if ($backgroundImage)
                background-image: url('{{ $backgroundImageUrl }}');
                background-size: cover;
                background-position: center;
            @endif
        }

        .canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $canvasWidth }}px;
            height: {{ $canvasHeight }}px;
            transform: scale({{ $formatFloat($scaleX) }}, {{ $formatFloat($scaleY) }});
            transform-origin: top left;
        }

        .element {
            position: absolute;
            transform-origin: top left;
            display: flex;
            flex-direction: column;
            justify-content: center;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .element img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .element--shape {
            padding: 0;
        }

        .element--text span {
            display: block;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="canvas">
        @foreach ($elements as $element)
            @php
                $type = $element['type'] ?? 'text';
                $x = (float) ($element['position']['x'] ?? 0);
                $y = (float) ($element['position']['y'] ?? 0);
                $width = (float) ($element['size']['width'] ?? $element['dimensions']['width'] ?? $canvasWidth);
                $height = (float) ($element['size']['height'] ?? $element['dimensions']['height'] ?? null);
                $zIndex = (int) ($element['z_index'] ?? 1);
                $opacity = max(0, min(1, (float) ($element['opacity'] ?? 1)));
                $rotation = (float) ($element['rotation'] ?? 0);
                $background = $element['background_color'] ?? null;
                $borderColor = $element['border']['color'] ?? null;
                $borderWidth = (float) ($element['border']['width'] ?? 0);
                $borderStyle = $borderWidth > 0 ? ($element['border']['style'] ?? 'solid') : 'none';
                $borderRadius = (float) ($element['border']['radius'] ?? 0);

                $font = $element['font'] ?? [];
                $fontFamily = $font['family'] ?? $layout['default_font_family'] ?? 'Inter, "Helvetica Neue", Helvetica, Arial, sans-serif';
                $fontSize = (float) ($font['size'] ?? 16);
                $fontWeight = $font['weight'] ?? 400;
                $fontColor = $font['color'] ?? '#111827';
                $lineHeight = $font['line_height'] ?? 1.2;
                $letterSpacing = (float) ($font['letter_spacing'] ?? 0);
                $textAlign = $element['text_align'] ?? 'left';
                $textTransform = $element['transform'] ?? 'none';
                $content = $element['content'] ?? '';
                $imageUrl = $element['image_url'] ?? ($element['content'] ?? null);
                $resolvedImageUrl = $resolveAssetUrl($imageUrl);
            @endphp

            <div
                class="element element--{{ $type }}"
                style="
                    left: {{ $formatFloat($x) }}px;
                    top: {{ $formatFloat($y) }}px;
                    width: {{ $formatFloat($width) }}px;
                    @if ($height !== null)
                        height: {{ $formatFloat($height) }}px;
                    @endif
                    z-index: {{ $zIndex }};
                    opacity: {{ $opacity }};
                    transform: rotate({{ $rotation }}deg);
                    @if ($background)
                        background-color: {{ $background }};
                    @endif
                    @if ($borderStyle !== 'none')
                        border: {{ $formatFloat($borderWidth) }}px {{ $borderStyle }} {{ $borderColor ?? '#111827' }};
                    @endif
                    border-radius: {{ $formatFloat($borderRadius) }}px;
                    text-align: {{ $textAlign }};
                    text-transform: {{ $textTransform }};
                    font-family: {{ $fontFamily }};
                    font-size: {{ $formatFloat($fontSize) }}px;
                    font-weight: {{ $fontWeight }};
                    color: {{ $fontColor }};
                    line-height: {{ is_numeric($lineHeight) ? $lineHeight : 1.2 }};
                    letter-spacing: {{ $formatFloat($letterSpacing) }}px;
                "
            >
                @if ($type === 'text')
                    <span>{!! nl2br(e($content)) !!}</span>
                @elseif ($type === 'image')
                    @if ($resolvedImageUrl)
                        <img src="{{ $resolvedImageUrl }}" alt="{{ $element['alt'] ?? '' }}">
                    @endif
                @elseif ($type === 'shape')
                    {{-- Shapes rely on background / border styling already applied --}}
                @endif
            </div>
        @endforeach
        </div>
    </div>
</body>
</html>
