<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

function delete_file($path)
{
    static $i = 0;
    $i++;
    echo "$i DELETE: " . $path . PHP_EOL;
    unlink($path);
}

function rename_file($from, $to)
{
    $newDir = dirname($to);
    if (!is_dir($newDir) && !mkdir($newDir, 0777, true) && !is_dir($newDir)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $newDir));
    }
    copy($from, $to);
    echo "CREATE: " . $to . PHP_EOL;
    if (file_exists($to)) {
        delete_file($from);
    }
}

$dir = new DirectoryIterator(MUSIC_DIR);
$flag = true;
$store = [];
$deleted = [];

$unknownStore = [];
foreach (seek_files($dir) as $file) {
    if (strpos($file["pathname"], "Unknown\\Unknown Album")) {
        $unknownStore[$file["filename"]] = $file;
    }
}
$flag = true;
foreach (seek_files($dir) as $file) {
    $basename = $file["basename"];
    $ext = $file["ext"];
    $filename = $file["filename"];
    $pathname = $file["pathname"];
    $dirname = $file["dirname"];
    $normalizedFilename = $file["normalizedFilename"];
    $normalizedBasename = $file["normalizedBasename"];
    $normalizedBasePathname = $dirname . "\\" . $normalizedBasename;
    $normalizedPathname = $dirname . "\\" . $normalizedFilename;
    if (strpos($filename, "_")) {
        if (strpos($pathname, "Classic Best Hits 100")) {
            $pos = strpos($pathname, "_");
            $renamePathname = substr($pathname, 0, $pos) . "：" . substr($pathname, $pos + 1);
            rename_file($pathname, $renamePathname);
        }
        if (strpos($pathname, "Love Story～Kazumasa Oda Songbook")) {
            $pos = strpos($pathname, "_");
            $renamePathname = substr($pathname, 0, $pos) . "／" . substr($pathname, $pos + 1);
            rename_file($pathname, $renamePathname);
        }
    }
    if (isset($unknownStore[$file["filename"]])) {
        $unknownPathname = $unknownStore[$file["filename"]]["pathname"];
        if ($flag) {
            delete_file($unknownPathname);
            $flag = false;
        } else {
            echo $pathname . PHP_EOL;
            echo $unknownPathname . PHP_EOL;
            exit;
        }
    }

    if (strpos($pathname, "拾いもの")) {
        $renamePathname = preg_replace('/iTunes Media\\\\Music\\\\[^\\\\]*/u', "iTunes Media\\Music\\Compilations", $pathname);
        if ($renamePathname !== $pathname) {
            if (!file_exists($renamePathname)) {
                rename_file($pathname, $renamePathname);
            } else {
                delete_file($pathname);
            }
        }
    }

    $renamePathname = strtr($pathname, rename_master());
    $renamePathname = strtr($renamePathname,adjust_duplicate_track_no());
    if ($renamePathname !== $pathname) {
        if (!file_exists($renamePathname)) {
            rename_file($pathname, $renamePathname);
        } else {
            delete_file($pathname);
        }
    }

    if (!in_array($ext, ["mp3", "m4a", "m4p"], true)) {
        if (strtolower($ext) === "wav") {
            $newPathname = str_replace('Music\iTunes\iTunes Media\Music', 'Music\Wav Files', $pathname);
            $newDir = dirname($newPathname);
            rename_file($pathname, $newPathname);
            continue;
        }
    }
    $search = $dirname . "\\" . $normalizedBasename;
    $search = strtr($search, ["[" => '?', "]" => '?']) . "*";
    $list = glob($search);

    if (in_array($normalizedPathname, $store, true) && !in_array($pathname, $deleted, true)) {
        foreach ($list as $target) {
            if ($target !== $normalizedPathname) {
                delete_file($target);
                $deleted[] = $target;
            }
        }
    }

    $store[] = $normalizedPathname;
    if ($filename === "Folder.jpg") {
        delete_file($pathname);
    }
    if (preg_match("/^AlbumArt.*\.jpg$/", trim($filename))) {
        delete_file($pathname);
    }
    if ($filename === "desktop.ini") {
        delete_file($pathname);
        continue;
    }
    if ($filename === "Thumbs.db") {
        delete_file($pathname);
        continue;
    }
}

function seek_files(DirectoryIterator $dir)
{
    foreach ($dir as $artist) {
        if ($artist->isDot()) {
            continue;
        }
        if (!$artist->isDir()) {
            continue;
        }
        if (!is_dir($artist->getPathname())) {
            continue;
        }
        $medias = new DirectoryIterator($artist->getPathname());
        foreach ($medias as $media) {
            if ($media->isDot()) {
                continue;
            }
            if (!$media->isDir()) {
                continue;
            }
            if (!is_dir($media->getPathname())) {
                continue;
            }
            $files = new DirectoryIterator($media->getPathname());
            foreach ($files as $file) {
                if ($file->isDot()) {
                    continue;
                }
                if ($file->isDir()) {
                    continue;
                }
                yield fileinfo($file);
            }
            $fileCount = file_count($medias);
            if ($fileCount === 0) {
                echo "EMPTY DIR: " . $medias->getPathname() . PHP_EOL;
                rmdir($medias->getPathname());
            }
        }
        $fileCount = file_count($artist);
        if ($fileCount === 0) {
            echo "EMPTY DIR: " . $artist->getPathname() . PHP_EOL;
            rmdir($artist->getPathname());
        }
    }
}

function fileinfo(SplFileInfo $file)
{
    $basename = $file->getBasename("." . $file->getExtension());
    $ext = $file->getExtension();
    $filename = $file->getFilename();
    $pathname = $file->getPathname();
    $dirname = $file->getPath();
    $normalizedBasename = preg_replace("/ \d$/", "", $basename);
    $normalizedBasename = preg_replace("/\(\d\)/", "", $normalizedBasename);
    $normalizedBasename = str_replace(" (Store)", "", $normalizedBasename);
    $normalizedFilename = trim($normalizedBasename) . "." . $ext;
    return compact("basename", "ext", "filename", "pathname", "dirname", "normalizedFilename", "normalizedBasename");
}

function rename_master()
{
    static $data = [];
    if (empty($data)) {
        foreach (load_csv(__DIR__ . "/artist_names.tsv") as $row) {
            $from = '\\' . $row[0] . '\\';
            $to = '\\' . $row[1] . '\\';
            $data[$from] = $to;
        }
        $data += [
            " - EP" => "",
            "(シングル)" => " - Single",
            "（シングル）" => " - Single",
            " (Store)" => "",
            " (from _Terraria_)" => "",
            "（" => "(",
            "）" => ")",
            "－" => "-",
            "＆" => "&",
            "．" => ".",
        ];
        $alphabet = [
            'a', 'b', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j', 'k',
            'l', 'm', 'n', 'o', 'p', 'q',
            'r', 's', 't', 'u', 'v', 'w',
            'x', 'y', 'z'];
        foreach ($alphabet as $chr) {
            $data[mb_convert_kana($chr, "R")] = $chr;
            $captal = strtoupper($chr);
            $data[mb_convert_kana($captal, "R")] = $captal;
        }
        foreach (range(0, 9) as $chr) {
            $data[mb_convert_kana($chr, "N")] = (string) $chr;
        }
    }
    return $data;
}

function adjust_duplicate_track_no()
{
    static $data = [];
    if (empty($data)) {
        foreach (range(0, 9) as $i) {
            $prefix = ($i > 0) ? "$i-" : "";
            foreach (range(1, 50) as $l) {
                $l = str_pad($l, 2, "0", STR_PAD_LEFT);
                $data["{$prefix}$l {$l}_"] = "{$prefix}{$l} ";
            }
        }

    }
    return $data;
}


function file_count(DirectoryIterator $dir)
{
    $files = new DirectoryIterator($dir->getPathname());
    $i = 0;
    foreach ($files as $file) {
        if ($file->isDot()) {
            continue;
        }
        if ($file->getFilename() === "desktop.ini") {
            continue;
        }
        if ($file->getFilename() === "Thumbs.db") {
            continue;
        }
        $i++;
    }
    return $i;
}


