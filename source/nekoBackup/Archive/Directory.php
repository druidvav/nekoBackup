<?php
namespace nekoBackup\Archive;

class Directory extends AbstractArchive
{
  protected $mode = 'full';

  /**
   * Дата базового архива для инкрементального бекапа
   * @var \DateTime
   */
  protected $baseDate;

  protected $include = array();
  protected $exclude = array();

  protected $metadataFilename;

  public function isEmpty()
  {
    return sizeof($this->include) == 0;
  }

  public function setInclude($paths)
  {
    if(is_string($paths)) {
      $this->include = array($paths);
    } else {
      $this->include = $paths;
    }
  }

  public function exclude($path)
  {
    if(substr($path, 0, 1) == '/') {
      $this->excludeAbsolute($path);
    } else {
      $this->excludeRelative($path);
    }
  }

  protected function excludeAbsolute($path)
  {
    // If we exclude main directory of archive - exclude whole archive
    if(sizeof($this->include) == 1 && $path == $this->include[0]) {
      unset($this->include[0]);
      return;
    }
    // FIXME исключить если главная директория включена в директорию исключения
    // FIXME убрать исключение, если оно не входит в главную директорию
    $this->exclude[] = $path;
  }

  protected function excludeRelative($path)
  {
    if(sizeof($this->include) > 1) {
      throw new \Exception('Relative excludes allowed only in wildcard directories');
    }
    $this->exclude[] = $this->include[0] . '/' . $path;
  }

  public function setIncrementalBase()
  {
    $this->mode = 'base';
    $this->baseDate = new \DateTime();
    $this->metadataFilename = $this->generateMetadataPath();
  }

  public function setIncremental(\DateTime $baseDate)
  {
    $curDate = new \DateTime();
    if($baseDate->format('Ymd') >= $curDate->format('Ymd')) {
      return false;
    }
    $this->mode = 'inc';
    $this->baseDate = $baseDate;
    $this->metadataFilename = $this->generateMetadataPath();
    if(file_exists($this->metadataFilename)) {
      return true;
    } else {
      $baseDate->modify('+1 day');
      return $this->setIncremental($baseDate);
    }
  }

  public function create()
  {
    $this->archiveFilename = $this->generateArchivePath('tar.gz');

    if(file_exists($this->archiveFilename)) {
      throw new Exception\ArchiveExists($this->archiveFilename);
    }

    $include = implode(' ', $this->include);

    $exclude = '';
    foreach($this->exclude as $path) {
      $exclude .= ' --exclude="' . $path . '"';
    }

    if($this->mode == 'base') {
      @unlink($this->metadataFilename);
    }

    $options = '';
    if(!empty($this->metadataFilename)) {
      @unlink("{$this->metadataFilename}.tmp");
      $options .= ' --listed-incremental="' . $this->metadataFilename . '.tmp"';
    }

    @unlink("{$this->archiveFilename}.tmp");
    system("nice -n 19 tar {$exclude} -czpf {$this->archiveFilename}.tmp {$include} {$options} 2>&1", $status);
    if($status == 0) {
      rename("{$this->archiveFilename}.tmp", $this->archiveFilename);
      if(!empty($this->metadataFilename)) {
        rename("{$this->metadataFilename}.tmp", $this->metadataFilename);
      }
    }
    return $status == 0;
  }

  protected function generateArchivePath($ext)
  {
    return parent::generateArchivePath($this->mode . '.' . $ext);
  }

  protected function generateMetadataPath($ext = 'meta')
  {
    $filename =
      $this->config->get('metadata') // Storage path
      . $this->baseDate->format('/Ymd/') // Date directory
      . $this->title // Requested filename
      . '.' . $ext; // Extension
    if(!is_dir(dirname($filename))) {
      mkdir(dirname($filename));
    }
    return $filename;
  }
}