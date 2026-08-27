#!/usr/bin/env python3
"""Génère GUIDE_INSTALLATION.pdf — guide client Windows."""
from __future__ import annotations

import re
from pathlib import Path

from fpdf import FPDF

ROOT = Path(__file__).resolve().parent
MD = ROOT / "GUIDE_INSTALLATION.md"
OUT = ROOT / "GUIDE_INSTALLATION.pdf"


class GuidePDF(FPDF):
    def footer(self) -> None:
        self.set_y(-12)
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(110, 110, 110)
        self.cell(0, 8, f"Page {self.page_no()}/{{nb}}  |  PGFE - Installation client Windows", align="C")


def clean(text: str) -> str:
    text = (
        text.replace("\u2014", "-")
        .replace("\u2013", "-")
        .replace("\u2026", "...")
        .replace("\u2019", "'")
        .replace("\u2018", "'")
        .replace("\u00a0", " ")
        .replace("\u2192", "->")
    )
    text = re.sub(r"\*\*(.+?)\*\*", r"\1", text)
    text = re.sub(r"`([^`]+)`", r"\1", text)
    text = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", r"\1 (\2)", text)
    return text.encode("latin-1", "replace").decode("latin-1")


def write_table(pdf: GuidePDF, rows: list[list[str]]) -> None:
    rows = [r for r in rows if not all(re.fullmatch(r":?-+:?", c.strip()) for c in r)]
    if not rows:
        return
    cols = max(len(r) for r in rows)
    usable = pdf.epw
    widths = [usable / cols] * cols
    # Prefer wider last column for links
    if cols == 3:
        widths = [usable * 0.28, usable * 0.42, usable * 0.30]
    elif cols == 2:
        widths = [usable * 0.35, usable * 0.65]

    for i, row in enumerate(rows):
        while len(row) < cols:
            row.append("")
        pdf.set_font("Helvetica", "B" if i == 0 else "", 9)
        pdf.set_fill_color(235, 238, 245) if i == 0 else pdf.set_fill_color(255, 255, 255)
        line_h = 4.2
        # compute row height
        heights = []
        for j, c in enumerate(row):
            lines = pdf.multi_cell(widths[j], line_h, clean(c.strip()), dry_run=True, output="LINES")
            heights.append(line_h * max(1, len(lines)) + 2)
        row_h = max(heights)
        if pdf.get_y() + row_h > pdf.page_break_trigger:
            pdf.add_page()
        x_start = pdf.l_margin
        y_start = pdf.get_y()
        x = x_start
        for j, c in enumerate(row):
            pdf.rect(x, y_start, widths[j], row_h)
            pdf.set_xy(x + 1, y_start + 1)
            pdf.multi_cell(widths[j] - 2, line_h, clean(c.strip()))
            x += widths[j]
        pdf.set_xy(x_start, y_start + row_h)
    pdf.ln(3)


def main() -> None:
    lines = MD.read_text(encoding="utf-8").splitlines()
    pdf = GuidePDF(format="A4")
    pdf.alias_nb_pages()
    pdf.set_auto_page_break(auto=True, margin=16)
    pdf.set_margins(16, 14, 16)
    pdf.add_page()

    in_code = False
    table_rows: list[list[str]] = []

    for raw in lines:
        line = raw.rstrip()

        if line.startswith("```"):
            if table_rows:
                write_table(pdf, table_rows)
                table_rows = []
            in_code = not in_code
            if not in_code:
                pdf.ln(2)
            continue

        if in_code:
            pdf.set_x(pdf.l_margin)
            pdf.set_font("Courier", "", 8)
            pdf.set_text_color(25, 25, 25)
            pdf.set_fill_color(245, 245, 245)
            pdf.multi_cell(pdf.epw, 4, clean(line) or " ", fill=True)
            continue

        if "|" in line and line.strip().startswith("|"):
            table_rows.append([c.strip() for c in line.strip().strip("|").split("|")])
            continue

        if table_rows:
            write_table(pdf, table_rows)
            table_rows = []

        pdf.set_x(pdf.l_margin)

        if not line.strip() or line.strip() == "---":
            pdf.ln(2)
            continue

        if line.startswith("# "):
            pdf.set_font("Helvetica", "B", 16)
            pdf.set_text_color(20, 40, 80)
            pdf.multi_cell(pdf.epw, 8, clean(line[2:]))
            pdf.ln(2)
        elif line.startswith("## "):
            pdf.ln(2)
            pdf.set_font("Helvetica", "B", 12)
            pdf.set_text_color(30, 60, 110)
            pdf.multi_cell(pdf.epw, 7, clean(line[3:]))
            pdf.ln(1)
        elif re.match(r"^\d+\.\s", line):
            pdf.set_font("Helvetica", "", 10)
            pdf.set_text_color(20, 20, 20)
            pdf.multi_cell(pdf.epw, 5, clean(line))
        else:
            pdf.set_font("Helvetica", "", 10)
            pdf.set_text_color(20, 20, 20)
            pdf.multi_cell(pdf.epw, 5, clean(line))

    if table_rows:
        write_table(pdf, table_rows)

    pdf.output(str(OUT))
    print(f"OK: {OUT}")


if __name__ == "__main__":
    main()
