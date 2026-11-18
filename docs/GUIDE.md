# Poultrilic guide

## Where things are

| Menu | What it is for |
|---|---|
| Farm overview | The morning page: eggs, hen-day, deaths, eggs per bag, birds now, what needs attention, active flocks |
| Flocks & daily records | Add/edit flocks, open a flock's page, record days (week → day) |
| Combine flocks | When flocks move into one house (e.g. 650 + 770 → 1,420) |
| Import from Excel | Upload the farm's sheet or a template, check it, import |
| Livestock & more | One menu item per farm unit type (Fish ponds, Goats…); *Set up farm units* to add or change types |
| Performance report | The full farm report for any period / flocks; *Download PDF* |
| Reports & analytics | Reports the admin designs: figures, grouping, period, flocks; Excel / PDF / CSV |

New permissions (granted to Super Admin and any role with `dashboard` by the migration; assign them to other roles
under Roles): *Import records, View reports, Manage reports, Merge flock, View livestock, Create / Update / Delete
livestock record, Manage livestock units, Manage livestock types.*

## Recording a flock-day

Only the day's own figures are entered: deaths, stolen/missing, culled/sold live, feed fed (bags), feed in store,
eggs laid / sold / broken / bad, medication, notes, optional sample weight and a physical bird count.

Everything cumulative is worked out by the **flock ledger** (`app/Services/FlockLedger.php`), walking the flock's
days in date order every time a day is added, changed, deleted or imported:

| Figure | How |
|---|---|
| Birds alive | birds at start − deaths − stolen − culled, to that day |
| Total deaths | sum of daily deaths |
| Feed to date | sum of bags fed |
| Eggs in farm | opening egg stock + laid − sold − broken − bad, to that day |
| Week / day | from the birds' age: age at arrival + days since arrival (a point-of-lay flock that arrived at 18 weeks is in *Week 19* in its first week) |

> **Fix in this release.** The old code summed only the current *week's* entries, so after week 1 a flock's bird
> count reset to "initial − this week's deaths", and the dashboard added up a running feed total (counting feed
> many times) and used hard-coded figures for flocks 1 and 2. All of that is gone; the migration recalculates
> every flock.

Selling more eggs than were laid that day is allowed (they come out of stock). Losses can never exceed the birds
alive.

### Data checks (flock page)

The farm's own typed totals are kept and compared with the daily figures. A check is listed where a difference
**first appears or changes** (not on every following day):

- reported birds alive ≠ birds alive from the daily losses (miscount, unrecorded death, missing birds)
- typed total deaths ≠ sum of daily deaths (deaths from before records began are allowed for)
- eggs in farm going negative (sales recorded against the wrong flock, or opening stock missing)
- days with no record; reports sent more than 2 days late

## The figures (`app/Services/PoultryMetrics.php`)

| Figure | Formula |
|---|---|
| Hen-day production % | eggs laid ÷ bird-days on laying days × 100 (bird-days = birds alive each day; days with no eggs, e.g. chicks, are left out) |
| Hen-housed eggs | eggs laid ÷ birds at the start of the period |
| Mortality % | deaths ÷ birds at the start of the period × 100 |
| Eggs per bag | eggs laid ÷ bags fed to laying flocks on laying days |
| Feed per bird per day | bags × bag weight (Settings) × 1000 ÷ bird-days, in grams |
| Feed per dozen eggs | kg fed on laying days ÷ (eggs ÷ 12) |
| Broken + bad % | (broken + bad) ÷ eggs laid × 100 |
| Typical report delay | median days between the day and when it was sent (imports with *Sent on*, mobile entries) |
| Money | sales invoiced, payments received and expenses from the finance pages, same dates |

Rates are worked out from each row's own days, so a whole-period figure is not an average of monthly figures.
When flocks are combined, farm totals count the birds once: at the start in the old flocks and at the end in the
new one.

**What needs attention** (overview and report) is rule-based: monthly mortality above 1% for layers / 3% for
young stock, deaths up 50% week on week, hen-day down more than 5 points week on week, stolen birds, broken + bad
above 3%, feed in store covering fewer days than the *feed reorder warning* setting, records more than 3 days
behind, reports typically more than 3 days late.

## Combining flocks

*Combine flocks* → tick the flocks, give the date, choose a new flock (started with their birds alive) or an
existing one (e.g. one created by an import). The old flocks are closed, keep their records, and show
"Combined into …".

## Importing from Excel

1. *Import from Excel* → choose the file (xlsx, xls, csv, ods).
2. Pick a flock if the sheet has no Flock column; tick *create missing flocks* to create flocks named in the sheet.
3. **Check the file**: every flock, date range, row count, deaths, eggs, which columns were recognised and any
   problems. Nothing is saved yet.
4. **Import**. A day already recorded is updated, never duplicated; running totals are recalculated once at the end.

Columns are matched by name, so the farm's own sheets import as they are (the cleaned *Daily Data* sheet and the
single-flock *Daily Log* were both tested). Recognised names include: Flock · Date / Report date · Week · Day ·
Deaths today · Total deaths · Stolen / Stolen (total) · Culled · Birds alive / Farm: birds alive · Feed today
(bags) / Feed used today (bags) · Feed in store / Feed remaining (bags) · Eggs laid · Eggs sold · Broken eggs ·
Bad eggs · Eggs in farm · Medication / Drugs · Avg weight (g) · Notes / Issues · Sent on.

- Eggs may be pieces or "11 Cr 12PC". Dates may be Excel dates, `2026-11-03`, or day-first `03/11/2026`.
- A running total (Stolen (total), Total deaths) is turned into daily figures when the daily column is missing.
- For a flock created by the import: birds at start = first day's birds alive + that day's losses; age from the
  Week/Day columns; opening egg stock from *Eggs in farm*.
- Every upload is listed under *Import history* with who imported it and what happened.

Templates (*Template* buttons) have a drop-down of flock / unit names and a *How to fill* sheet. Each flock's
*Excel* button exports its records in the template layout, so they can be corrected in Excel and imported back.

Livestock types import the same way with their own template (unit column, Date, one column per field).

## Livestock & other farm units

*Set up farm units* → start from a template (Cattle, Goats & sheep, Fish ponds, Pigs, Crops & produce) or create
your own type. For each type:

- **Unit is a…** (Pond, Herd, Pen, Plot) and **animals are called** (Fish, Cattle…)
- **Fields**: label, kind (whole number, decimal, amount ₦, text, date, choice list, yes/no), unit, how it totals
  (add up, average, latest, highest, lowest, count of days) and its **effect on the head count**: *adds*
  (births, purchases, stocking), *takes* (deaths, sales, harvest), *is the count* (a physical count), or none.

The head count of each unit is walked from its opening count through its records, like the flock ledger, and can
never go below zero. Each type gets its own menu item, record form, unit pages, Excel template/export, totals by
unit and month, and appears in the performance report and the report designer.

## Reports

- **Performance report**: at a glance, findings, flocks, hen-day / eggs-per-bag / mortality / eggs by month,
  data checks, every livestock type, money. Any period and set of flocks. PDF download.
- **Reports & analytics**: *New report* → name, what to report on (poultry or a livestock type), up to 8 figures,
  group by day / week / month / flock-unit / whole period, period (relative periods end on the latest record),
  chart type, flocks/units, "one line per flock" and "pin to overview". Each report opens with chart + table and
  exports to Excel, PDF and CSV. *Add starter reports* creates six useful ones in one click.
