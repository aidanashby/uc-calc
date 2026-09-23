# Your to-do list: UC Calculator

Everything left for you to do, in order, as of 23rd September 2026. Tick each box as you go.

v1.2.0 is released on GitHub (see `CHANGELOG.md`). The live site stays on v1.0.0 until you update it.

---

## 1. Test v1.1.0 on the dev site

- [x] Update the dev site to v1.1.0.
- [x] Settings: a saved change shows **Changed from the default of …**; **Reset to defaults** works.
- [x] One adult aged 25 or over, no children: £98.05 income, £117.46 essentials, £19.41 shortfall.
- [x] Couple with three children aged 5 to 15: £360.61 income and the benefit cap note.
- [x] The "Income per week" and "Essentials per week" labels are dark green and easy to read.
- [x] LiteSpeed: works with **Load JS Deferred** set to Off (your normal setting) and to Deferred.
- [ ] Screen reader check with NVDA (free, Windows): press Tab to reach **One more adult**, then press Enter. NVDA should read out the new number.

## 2. Test v1.2.0 on the dev site

- [ ] Update the dev site from v1.1.0 to v1.2.0: **Dashboard → Updates → Check again**, then update UC Calculator. This is the first update through v1.1.0's improved updater.

Then check:

- [ ] On a desktop screen, the calculator shows three columns: household, essentials, and the result at the top of the third.
- [ ] Make the browser window narrower, to just above tablet width. Income and essentials stack inside the result panel and no figure is cut off.
- [ ] Hover over an ⓘ button. Its explanation appears as an overlay and stays open while you move the mouse onto it.
- [ ] Click an ⓘ button. The overlay stays open until you press Escape or click somewhere else.
- [ ] On a tablet or phone, tap an ⓘ button. The explanation opens below the row, and tapping again closes it.
- [ ] With no children, the **School uniform and shoes** row doesn't appear.

## 3. Update the live site

Wait until step 2 passes, so the live site gets the layout changes in the same update.

- [ ] In the live site's wp-admin, go to **Dashboard → Updates**, click **Check again**, and update UC Calculator. v1.0.0 only looks for updates while you're in wp-admin, so **Check again** matters this once. From v1.1.0, WordPress finds updates in the background too.
- [ ] Go to **Settings → UC Calculator** and look for fields marked **Changed from the default of …**. You'll probably see **Work allowance — no housing (monthly)** showing 673.
- [ ] If none of the marked values were set on purpose, click **Reset to defaults**. Otherwise change the unwanted ones back to their default and click **Save Settings**.
- [ ] Open the calculator page and repeat the two figure checks from step 1 (£98.05 and £360.61).

## 4. Monitoring routine

- [x] Create the **UC Calc env** cloud environment with the source websites and common package managers allowed.
- [x] Attach the **aidanashby/uc-calc** repository. Your first run on 23rd September couldn't save its report because the routine had no repository attached, so Claude attached it and re-ran it.
- [x] Test run. The re-run saved [its first report](../reports/uc-updates/2026-09-23.md). Claude applied the two copy changes it recommended (the deductions figure and the school uniform text), which go out with the next release.
- [ ] Let the routine reach the NHS Healthy Start site, which was blocked. Go to [claude.ai/code/routines](https://claude.ai/code/routines), open **UC Calculator monitoring**, select **Edit**, click the cloud icon showing **UC Calc env**, then its settings (gear) icon. Add `*.nhs.uk` on a new line in **Allowed domains** and click **Save changes**.

The next scheduled run is on 7th December 2026. You'll get a push notification and an email when it finishes. Always read the message, since a green tick only means it didn't crash.

## 5. GitHub security

- [x] Rules protecting `main` (no force-push or deletion) and release tags (only admins can create, move or delete `v*` tags). Set up 23rd September 2026.
- [ ] Check two-factor authentication is on for your GitHub account: **Settings → Password and authentication**.
- [ ] Optional, once 2FA is on: on the live site's **Plugins** screen, click **Enable auto-updates** for UC Calculator.

## 6. Decisions waiting on you

These need your judgement, not code.

- [ ] How to handle housing costs, starting with the "money left" wording. Claude's recommendation is in the 23rd September session. Once you've decided, it gets its own issue.
- [ ] Travel cost for working adults: the calculator uses £22 a week, which matches First Bus's Weston-super-Mare weekly ticket. The Bristol and Bath weekly ticket is £28, and the info text says the figure is "for Bristol". Was £22 chosen on purpose (for example, as a few days' fares rather than a full weekly ticket)? If not, it should be £28. Tell Claude either way.
- [ ] [Issue #3](https://github.com/aidanashby/uc-calc/issues/3): three modelling choices a critic could challenge.
- [ ] [Issue #5](https://github.com/aidanashby/uc-calc/issues/5): whether to change the mid-green focus outline to dark green in the essentials list, so it meets contrast rules.

---

When every box is ticked, delete this file.
