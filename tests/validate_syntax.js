const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const errors = [];

try {
  JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8').replace(/^\uFEFF/, ''));
} catch (error) {
  errors.push(`package.json: ${error.message}`);
}

function walk(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    if (['.git', 'node_modules', '.pnpm-store', 'vendor'].includes(entry.name)) continue;
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory()) walk(fullPath);
    else if (entry.isFile() && fullPath.endsWith('.php') && fs.statSync(fullPath).size === 0) {
      errors.push(`${path.relative(root, fullPath)}: empty PHP file`);
    }
  }
}

walk(root);

// Contrato estático del diagnóstico: permite validar el aislamiento aunque el
// PHP local no tenga instalado pdo_sqlite ni pdo_mysql.
const diagnosisApi = fs.readFileSync(path.join(root, 'api', 'diagnoses.php'), 'utf8');
const diagnosisSchema = fs.readFileSync(path.join(root, 'api', 'database.php'), 'utf8');
const requiredDiagnosisMarkers = [
  ['api/diagnoses.php', diagnosisApi, "require_auth()"],
  ['api/diagnoses.php', diagnosisApi, "'credits_consumed' => 0"],
  ['api/diagnoses.php', diagnosisApi, "captation_diagnoses"],
  ['api/database.php', diagnosisSchema, 'CREATE TABLE IF NOT EXISTS captation_diagnoses']
];
for (const [file, content, marker] of requiredDiagnosisMarkers) {
  if (!content.includes(marker)) errors.push(`${file}: missing diagnosis contract marker: ${marker}`);
}

if (errors.length) {
  console.error(errors.join('\n'));
  process.exit(1);
}

console.log('Syntax validation passed: package.json and PHP files are valid.');
