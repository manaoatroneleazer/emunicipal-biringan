<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($subject ?? 'eMunicipal', ENT_QUOTES, 'UTF-8') ?></title>
<style>
  /* Biringan City Email Design System */
  body          { margin:0; padding:0; background:#F8F6F8; font-family:Arial,Helvetica,sans-serif; font-size:15px; color:#241C29; line-height:1.6; }
  .wrapper      { max-width:600px; margin:24px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(74,31,70,0.08); }
  .header       { background:#4A1F46; padding:28px 32px; }
  .header h1    { margin:0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:-0.01em; }
  .header p     { margin:6px 0 0; color:rgba(255,255,255,0.85); font-size:13px; letter-spacing:0.02em; }
  .body         { padding:32px; background:#ffffff; }
  .body h2      { font-size:20px; font-weight:700; color:#4A1F46; margin:0 0 16px; line-height:1.3; }
  .body p       { line-height:1.65; margin:0 0 16px; color:#241C29; }
  .ref-box      { background:#EEF2F7; border-left:4px solid #4A1F46; padding:16px 20px; margin:24px 0; border-radius:0 6px 6px 0; }
  .ref-box .label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:#756C78; margin-bottom:6px; }
  .ref-box .ref { font-family:'Courier New',Courier,monospace; font-size:1.3em; font-weight:700; color:#4A1F46; letter-spacing:0.02em; word-break:break-all; }
  .status-badge { display:inline-block; padding:5px 12px; border-radius:5px; font-size:13px; font-weight:600; letter-spacing:0.01em; }
  .status-pending              { background:#E5E7EB; color:#374151; }
  .status-under_verification   { background:#DBEAFE; color:#1D4ED8; }
  .status-additional_info      { background:#FEF3C7; color:#92400E; }
  .status-approved             { background:#D1FAE5; color:#065F46; }
  .status-rejected             { background:#FEE2E2; color:#991B1B; }
  .status-ready_for_claiming   { background:#CCFBF1; color:#0F766E; }
  .status-completed            { background:#BBF7D0; color:#14532D; }
  .status-cancelled            { background:#D1D5DB; color:#1F2937; }
  .btn          { display:inline-block; background:#4A1F46; color:#ffffff !important; padding:12px 28px; border-radius:6px; text-decoration:none; font-weight:600; font-size:14px; margin-top:12px; transition:background 0.2s; }
  .btn:hover    { background:#2B173D; }
  .remark-box   { background:#FEF3C7; border:1px solid #F59E0B; border-radius:6px; padding:14px 18px; margin:20px 0; font-size:14px; line-height:1.6; }
  .remark-box.rejection { background:#FEE2E2; border-color:#B91C1C; }
  .remark-box strong { display:block; margin-bottom:8px; color:#241C29; }
  .footer       { background:#F1F3F5; padding:24px 32px; text-align:center; font-size:12px; color:#6B7280; }
  .footer a     { color:#2563A8; text-decoration:none; }
  .footer .disclaimer { margin:12px 0 0; font-size:11px; color:#9CA3AF; line-height:1.5; }
  table.info    { width:100%; border-collapse:collapse; margin:20px 0; font-size:14px; }
  table.info td { padding:10px 0; border-bottom:1px solid #E8E0E8; vertical-align:top; }
  table.info td:first-child { width:42%; font-weight:600; color:#756C78; }
  table.info td:last-child { color:#241C29; }
  table.info tr:last-child td { border-bottom:none; }
  
  /* Mobile responsive */
  @media only screen and (max-width: 600px) {
    .wrapper { margin:12px; border-radius:6px; }
    .header { padding:20px 24px; }
    .header h1 { font-size:18px; }
    .body { padding:24px 20px; }
    .body h2 { font-size:18px; }
    .footer { padding:20px 24px; }
    .btn { display:block; width:100%; text-align:center; box-sizing:border-box; }
    .ref-box { padding:12px 16px; }
    table.info td { padding:8px 0; font-size:13px; }
  }
</style>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0;padding:0;">
  <tr>
    <td align="center" bgcolor="#F8F6F8" style="padding:24px 12px;">
      <table role="presentation" class="wrapper" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(74,31,70,0.08);">
        <!-- Header -->
        <tr>
          <td class="header" bgcolor="#4A1F46" style="padding:28px 32px;">
            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;font-family:Arial,Helvetica,sans-serif;letter-spacing:-0.01em;">eMunicipal</h1>
            <p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:13px;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.02em;">Biringan City Administration</p>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td class="body" bgcolor="#FFFFFF" style="padding:32px;">
            <?= $body ?? '' ?>
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td class="footer" bgcolor="#F1F3F5" style="padding:24px 32px;text-align:center;font-size:12px;color:#6B7280;font-family:Arial,Helvetica,sans-serif;">
            <p style="margin:0 0 8px;line-height:1.6;">
              This is an automated message from the eMunicipal Online Document Request System.
            </p>
            <p style="margin:0 0 12px;line-height:1.6;">
              Biringan City Administration &nbsp;|&nbsp;
              <a href="<?= htmlspecialchars($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', ENT_QUOTES, 'UTF-8') ?>" style="color:#2563A8;text-decoration:none;">eMunicipal Portal</a>
            </p>
            <p class="disclaimer" style="margin:12px 0 0;font-size:11px;color:#9CA3AF;line-height:1.5;">
              Biringan City and all organizations, services, records, and characters presented are fictional and created for academic demonstration.
            </p>
            <p style="margin:12px 0 0;font-size:12px;color:#6B7280;">Please do not reply to this email.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
