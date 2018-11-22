



### 从master分支，打印分支
1. 切换到被copy的分支（master），并且从远端拉取最新版本 
	<pre>
	$git checkout master
	$git pull
	</pre>	

2. 从当前分支拉copy开发分支 <br>
	<pre>
	$git checkout -b dev
	Switched to a new branch 'dev'
	</pre>
3. 把新建的分支push到远端
	<pre>
	$git push origin dev
	</pre>
4. 拉取远端分支
	<pre>
	$git pull
	There is no tracking information for the current branch.
	Please specify which branch you want to merge with.
	See git-pull(1) for details.
	git pull <remote> <branch>
	If you wish to set tracking information for this branch you can do so with:
	git branch --set-upstream-to=origin/<branch> dev
	经过验证，当前的分支并没有和本地分支关联，根据提示进行下一步：
	</pre>
5. 关联
	<pre>
	$git branch --set-upstream-to=origin/dev
	</pre>
6. 再次拉取 验证
	<pre>
	$git pull
	</pre>

### 合并分支
1. 合并分支，暂不提交
	<pre>
	git merge --squash --no-commit 分支名
	</pre>


### 删除临时分支
1. 删除本地分支 
	<pre>
	git branch -d temp
	</pre>
2. 删除远程分支
	<pre>
	git push origin --delete temp
	</pre>










