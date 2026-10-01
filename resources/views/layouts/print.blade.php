{{--
    Print / PDF layout (receipts). Self-contained inline CSS so the same view renders in the browser
    print dialog and in DomPDF (which cannot run Vite/Tailwind). Sections: title, content.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Poppins, Arial, sans-serif; font-size: 12px; color: #1e293b; background: #fff; }
        .page { max-width: 720px; margin: 0 auto; padding: 24px; }
        h1 { font-size: 20px; margin: 0; color: #164e63; }
        h2 { font-size: 13px; margin: 18px 0 6px; color: #164e63; text-transform: uppercase; letter-spacing: .04em; }
        .muted { color: #64748b; }
        .row { display: flex; justify-content: space-between; gap: 16px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 4px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        tr.total td { font-weight: bold; border-top: 2px solid #0e7490; }
        .ref { font-family: DejaVu Sans Mono, monospace; font-size: 18px; font-weight: bold; letter-spacing: .08em; color: #0e7490; }
        .right { text-align: right; }
        .label-col { width: 30%; }
        .footnote { margin-top: 24px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; background: #ecfeff; color: #155e75; font-size: 11px; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button { padding: 8px 14px; border: 0; border-radius: 8px; background: #0e7490; color: #fff; font: inherit; cursor: pointer; }
        @media print { .toolbar { display: none; } .page { padding: 0; } }
    </style>
</head>
<body>
    <div class="page">
        @yield('content')
    </div>
</body>
</html>
