# Pearls

## Backfilling Comics
```
let currentDate = new Date("2024-06-05");
let endDate = new Date("2024-06-14");
while (currentDate < endDate) {
    // Fetch the URL
    let url = await fetch('https://alexbeals.com/projects/pearls/php/download.php?dl=<code>&date=' + currentDate.toISOString().split('T')[0]);
    console.log(await url.text());

    // Increment the date
    currentDate.setDate(currentDate.getDate() + 1);
}
```