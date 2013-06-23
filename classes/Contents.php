<?php

class Forum_Contents
{
    /**
     * The path of the base folder of the CMSimple installation.
     *
     * @var string
     */
    protected $baseFolder;

    /**
     * The path of the configured data folder.
     *
     * @var string
     */
    protected $dataFolder;

    /**
     * The path of the default data folder, which will be used
     * if the configured data folder is empty.
     *
     * @var string
     */
    protected $defaultDataFolder;

    /**
     * An associative array from forum names to their lock handles.
     *
     * @var array
     */
    protected $lockHandles = array();

    /**
     * Constructs an instance.
     *
     * @param string $baseFolder        The path of the base folder of the
     *                                  CMSimple installation.
     * @param string $dataFolder        The path of the configured data folder.
     * @param string $defaultDataFolder The path of the default data folder.
     */
    public function __construct($baseFolder, $dataFolder, $defaultDataFolder)
    {
        $this->baseFolder = $baseFolder;
        if (substr($dataFolder, -1)) {
            $dataFolder .= '/';
        }
        $this->dataFolder = $dataFolder;
        if (substr($defaultDataFolder, -1)) {
            $defaultDataFolder .= '/';
        }
        $this->defaultDataFolder = $defaultDataFolder;
    }

    /**
     * Returns the path of the data folder of the forums or an individual forum.
     *
     * @param string $forum The name of a forum.
     *                      <var>null</var> means the general forum folder.
     *
     * @return string
     */
    function dataFolder($forum = null)
    {
        if ($this->dataFolder == '') {
            $filename = $this->defaultDataFolder;
        } else {
            $filename = $this->baseFolder . $this->dataFolder;
        }
        if (isset($forum)) {
            $filename .= $forum . '/';
        }
        if (file_exists($filename)) {
            if (!is_dir($filename)) {
                e('cntopen', 'folder', $filename); // exception
            }
        } else {
            if (!mkdir($filename, 0777, true)) {
                e('cntsave', 'folder', $filename); // exception
            }
        }
        return $filename;
    }

    /**
     * Locks resp. unlocks a forum's database.
     *
     * @param string $forum The name of a forum.
     * @param int    $op    The locking operation.
     *
     * @return void
     *
     * @todo Error handling.
     */
    function lock($forum, $op)
    {
        $filename = $this->dataFolder($forum) . '.lock';
        touch($filename);
        switch ($op) {
            case LOCK_SH:
            case LOCK_EX:
                $this->lockHandles[$forum] = fopen($filename, 'r+b');
                flock($this->lockHandles[$forum], $op);
                break;
            case LOCK_UN:
                flock($this->lockHandles[$forum], $op);
                fclose($this->lockHandles[$forum]);
                unset($this->lockHandles[$forum]);
                break;
        }
    }

    /**
     * Returns a forum's topics.
     *
     * @param string $forum The name of a forum.
     *
     * @return array
     */
    function getTopics($forum)
    {
        $filename = $this->dataFolder($forum) . 'topics.dat';
        if (is_readable($filename)
            && ($contents = file_get_contents($filename))
        ) {
            $data = unserialize($contents);
        } else {
            $data = array();
        }
        return $data;
    }

    /**
     * Writes a forum's topics.
     *
     * @param string $forum The name of a forum.
     * @param array  $data  The topic data.
     *
     * @return void
     */
    function setTopics($forum, $data)
    {
        $filename = $this->dataFolder($forum) . 'topics.dat';
        $contents = serialize($data);
        if (!file_put_contents($filename, serialize($data))) {
            e('cntsave', 'file', $filename); // throw exeption
        }
    }

    /**
     * Returns a topic.
     *
     * @param string $forum The name of a forum.
     * @param string $tid   A topic ID.
     *
     * @return array
     */
    function getTopic($forum, $tid)
    {
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        if (is_readable($filename)
            && ($contents = file_get_contents($filename))
        ) {
            $data = unserialize($contents);
        } else {
            $data = array();
        }
        return $data;
    }

    /**
     * Writes the topic $tid.
     *
     * @param string $forum The name of a forum.
     * @param string $tid   A topic ID.
     * @param array  $data  The new topic contents.
     *
     * @return void
     */
    function setTopic($forum, $tid, $data)
    {
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        $contents = serialize($data);
        if (!file_put_contents($filename, $contents)) {
            e('cntsave', 'file', $filename); // exception
        }
    }

    /**
     * Returns <var>$id</var>, if it's a valid ID, <var>false</var> otherwise.
     *
     * @param string $id An ID to check.
     *
     * @return string
     */
    function cleanId($id)
    {
        return preg_match('/^[a-f0-9]{13}+$/u', $id)
                ? $id : false;
    }

}

?>
