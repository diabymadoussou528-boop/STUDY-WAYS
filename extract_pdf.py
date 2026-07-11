from pathlib import Path
from pypdf import PdfReader
p = Path(r'C:\Users\LENOVO\Documents\RAPPORT-Website challenge.pdf')
reader = PdfReader(p)
out = Path('pdf_reference_text.txt')
with out.open('w', encoding='utf-8') as f:
    for i, page in enumerate(reader.pages, 1):
        f.write(f'--- PAGE {i} ---\n')
        f.write(page.extract_text() or '')
        f.write('\n\n')
print(out.resolve())
