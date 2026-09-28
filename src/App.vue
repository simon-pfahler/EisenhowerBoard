<script setup lang="ts">
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import TaskBoard from './TaskBoard.vue'
</script>

<template>
	<NcContent app-name="eisenhowerboard">
		<NcAppContent :class="$style.content">
			<TaskBoard />
		</NcAppContent>
	</NcContent>
</template>

<style module>
.content {
	display: flex;
	flex-direction: column;
	margin: 16px;
}
</style>
