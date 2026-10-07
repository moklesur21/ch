Here is a sample CSV template for your UTM builder sheet. You can copy this code block, save it as a file named `utm-builder.csv`, and import it directly into a Google Sheet, or simply copy and paste it into cell `A1` of a new sheet.

```csv
Description,Destination URL,utm_source,utm_medium,utm_campaign,Final URL
Newsletter Trial Sequence,https://puredevs.com/,newsletter,email,proof2026_trial_seq,https://puredevs.com/?utm_source=newsletter&utm_medium=email&utm_campaign=proof2026_trial_seq
YouTube Launch Video,https://puredevs.com/pricing,youtube,video,proof2026_search_category,https://puredevs.com/pricing?utm_source=youtube&utm_medium=video&utm_campaign=proof2026_search_category
Capterra Directory Listing,https://puredevs.com/,capterra,listing,proof2026_search_alternatives,https://puredevs.com/?utm_source=capterra&utm_medium=listing&utm_campaign=proof2026_search_alternatives

```

### Pro-Tip for your Google Sheet:

Instead of typing the final URL manually, you can use a simple formula in the **Final URL** column so it builds automatically:
`=B2 & "?utm_source=" & C2 & "&utm_medium=" & D2 & "&utm_campaign=" & E2`

Once your VA has this set up, whenever anyone on the team needs a tracked link, they just pick from your strict list of approved values (`source`, `medium`, `campaign`) and the sheet generates the clean link safely.

Ready to tackle **Microsoft Clarity** next?